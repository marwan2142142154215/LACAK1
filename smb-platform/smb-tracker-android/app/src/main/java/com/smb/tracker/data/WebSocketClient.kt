package com.smb.tracker.data

import android.util.Log
import com.smb.tracker.BuildConfig
import kotlinx.coroutines.*
import okhttp3.OkHttpClient
import okhttp3.Request
import okhttp3.Response
import okhttp3.WebSocket
import okhttp3.WebSocketListener
import org.json.JSONObject
import java.util.concurrent.TimeUnit
import kotlin.math.min
import kotlin.random.Random

interface CommandHandler {
    suspend fun onCommand(commandId: String, commandType: String, payload: JSONObject?)
}

class WebSocketClient(
    private val deviceId: String,
    private val token: String,
    private val store: DeviceStore?,
    private val handler: CommandHandler,
) {
    private var ws: WebSocket? = null
    private var reconnectJob: Job? = null
    private val client = OkHttpClient.Builder()
        .readTimeout(0, TimeUnit.MILLISECONDS)
        .pingInterval(20, TimeUnit.SECONDS)
        .build()
    private var attempt = 0
    @Volatile private var stopped = false

    fun connect() {
        stopped = false
        val url = "${BuildConfig.WS_BASE_URL}/ws?device_id=$deviceId&token=$token"
        val req = Request.Builder().url(url).build()
        ws = client.newWebSocket(req, listener)
    }

    private val listener = object : WebSocketListener() {
        override fun onOpen(webSocket: WebSocket, response: Response) {
            attempt = 0
            Log.i("SMB-WS", "connected")
        }

        override fun onMessage(webSocket: WebSocket, text: String) {
            try {
                val msg = JSONObject(text)
                if (msg.optString("type") == "device.command") {
                    val cmd = msg.getJSONObject("command")
                    CoroutineScope(Dispatchers.IO).launch {
                        handler.onCommand(cmd.getString("id"), cmd.getString("command_type"), cmd.optJSONObject("payload"))
                    }
                }
            } catch (e: Exception) {
                Log.w("SMB-WS", "bad message", e)
            }
        }

        override fun onFailure(webSocket: WebSocket, t: Throwable, response: Response?) {
            scheduleReconnect()
        }

        override fun onClosed(webSocket: WebSocket, code: Int, reason: String) {
            if (!stopped) scheduleReconnect()
        }
    }

    private fun scheduleReconnect() {
        if (stopped) return
        val delayMs = min(30_000.0, (1_000.0 * Math.pow(2.0, attempt.toDouble()))).toLong() + Random.nextLong(0, 500)
        attempt++
        reconnectJob = CoroutineScope(Dispatchers.IO).launch {
            delay(delayMs)
            if (!stopped) connect()
        }
    }

    fun send(message: String) {
        ws?.send(message)
    }

    fun close() {
        stopped = true
        reconnectJob?.cancel()
        ws?.close(1000, "bye")
    }
}
