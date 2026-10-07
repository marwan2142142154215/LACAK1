package com.smb.tracker.service

import android.app.Notification
import android.app.NotificationChannel
import android.app.NotificationManager
import android.app.Service
import android.content.Intent
import android.content.pm.ServiceInfo
import android.os.Build
import android.os.IBinder
import android.os.PowerManager
import android.util.Log
import com.smb.tracker.data.CommandHandler
import com.smb.tracker.data.DeviceApiClient
import com.smb.tracker.data.WebSocketClient
import kotlinx.coroutines.*
import kotlinx.coroutines.flow.first
import org.json.JSONObject

class TrackingService : Service(), CommandHandler {

    private lateinit var scope: CoroutineScope
    private var wsClient: WebSocketClient? = null
    private var wakeLock: PowerManager.WakeLock? = null

    override fun onCreate() {
        super.onCreate()
        scope = CoroutineScope(SupervisorJob() + Dispatchers.IO)
    }

    override fun onStartCommand(intent: Intent?, flags: Int, startId: Int): Int {
        createChannel()
        val notification = Notification.Builder(this, CHANNEL).setContentTitle("SMB Lacak").setContentText("Device terhubung & dipantau").setSmallIcon(android.R.drawable.ic_dialog_info).setOngoing(true).build()
        if (Build.VERSION.SDK_INT >= 29) {
            startForeground(1, notification, ServiceInfo.FOREGROUND_SERVICE_TYPE_DATA_SYNC)
        } else {
            startForeground(1, notification)
        }

        scope.launch {
            val store = com.smb.tracker.data.DeviceStore(applicationContext)
            val deviceId = store.deviceId.first()
            val token = store.token.first()
            if (deviceId == null || token == null) {
                Log.w("SMB", "Belum teregistrasi")
                return@launch
            }

            wsClient = WebSocketClient(deviceId, token, store, this@TrackingService)
            wsClient?.connect()

            launch { heartbeatLoop(deviceId, token) }
        }
        return START_STICKY
    }

    private suspend fun heartbeatLoop(deviceId: String, token: String) {
        while (true) {
            val battery = if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.LOLLIPOP) {
                val bm = getSystemService(BATTERY_SERVICE) as android.os.BatteryManager
                bm.getIntProperty(android.os.BatteryManager.BATTERY_PROPERTY_CAPACITY)
            } else null
            DeviceApiClient.heartbeat(deviceId, token, battery)
            wsClient?.send(JSONObject().put("type", "device.heartbeat").put("payload", JSONObject().put("battery_level", battery)).toString())
            delay(30_000)
        }
    }

    override suspend fun onCommand(commandId: String, commandType: String, payload: JSONObject?) {
        when (commandType) {
            "lock" -> {
                DeviceApiClient.ack(commandId, tokenFallback(), "EXECUTING")
                lockTaskEffect()
                DeviceApiClient.ack(commandId, tokenFallback(), "SUCCESS", result = "locked")
            }
            else -> {
                DeviceApiClient.ack(commandId, tokenFallback(), "SUCCESS", result = "unsupported_or_noop_${commandType}")
            }
        }
    }

    private suspend fun tokenFallback(): String {
        val store = com.smb.tracker.data.DeviceStore(applicationContext)
        return store.token.first() ?: ""
    }

    private fun lockTaskEffect() {
        val i = packageManager.getLaunchIntentForPackage(packageName)
        i?.addFlags(Intent.FLAG_ACTIVITY_NEW_TASK)
        if (i != null) startActivity(i)
    }

    override fun onDestroy() {
        wsClient?.close()
        scope.cancel()
        super.onDestroy()
    }

    override fun onBind(intent: Intent?): IBinder? = null

    private fun createChannel() {
        if (Build.VERSION.SDK_INT >= 26) {
            val ch = NotificationChannel(CHANNEL, "SMB Lacak", NotificationManager.IMPORTANCE_LOW)
            (getSystemService(NOTIFICATION_SERVICE) as NotificationManager).createNotificationChannel(ch)
        }
    }

    companion object {
        const val CHANNEL = "smb_tracking"
    }
}
