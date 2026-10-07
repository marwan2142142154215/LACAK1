package com.smb.tracker.data

import android.content.Context
import com.smb.tracker.BuildConfig
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.withContext
import okhttp3.MediaType.Companion.toMediaType
import okhttp3.MultipartBody
import okhttp3.OkHttpClient
import okhttp3.Request
import okhttp3.RequestBody.Companion.asRequestBody
import okhttp3.RequestBody.Companion.toRequestBody
import org.json.JSONObject
import java.io.File

object DeviceApiClient {
    private val client = OkHttpClient()
    private val json = "application/json; charset=utf-8".toMediaType()

    suspend fun register(registrationCode: String): Result<Pair<String, String>> = withContext(Dispatchers.IO) {
        try {
            val body = JSONObject().put("registration_code", registrationCode).toString().toRequestBody(json)
            val req = Request.Builder().url("${BuildConfig.API_BASE_URL}/api/v1/devices/register").post(body).build()
            client.newCall(req).execute().use { resp ->
                val text = resp.body?.string().orEmpty()
                val obj = JSONObject(text)
                if (!resp.isSuccessful || !obj.optBoolean("success", false)) {
                    return@withContext Result.failure(Exception(obj.optString("message", "Registrasi gagal")))
                }
                val data = obj.getJSONObject("data")
                Result.success(data.getString("device_id") to data.getString("token"))
            }
        } catch (e: Exception) {
            Result.failure(e)
        }
    }

    suspend fun heartbeat(deviceId: String, token: String, battery: Int?, lat: Double? = null, lng: Double? = null, accuracy: Float? = null): Boolean = withContext(Dispatchers.IO) {
        try {
            val payload = JSONObject().put("battery_level", battery).put("app_version", BuildConfig.VERSION_NAME).put("android_api", android.os.Build.VERSION.SDK_INT)
            if (lat != null && lng != null) {
                payload.put("latitude", lat).put("longitude", lng)
                if (accuracy != null) payload.put("accuracy", accuracy)
                payload.put("source", "fused")
            }
            val body = payload.toString().toRequestBody(json)
            val req = Request.Builder().url("${BuildConfig.API_BASE_URL}/api/v1/devices/$deviceId/heartbeat")
                .addHeader("Authorization", "Bearer $token").post(body).build()
            client.newCall(req).execute().use { it.isSuccessful }
        } catch (e: Exception) {
            false
        }
    }

    suspend fun ack(commandId: String, token: String, status: String, result: String? = null, error: String? = null): Boolean = withContext(Dispatchers.IO) {
        try {
            val body = JSONObject().put("status", status).let { if (result != null) it.put("result", result) else it }
                .let { if (error != null) it.put("error_message", error) else it }.toString().toRequestBody(json)
            val req = Request.Builder().url("${BuildConfig.API_BASE_URL}/api/v1/commands/$commandId/ack")
                .addHeader("Authorization", "Bearer $token").post(body).build()
            client.newCall(req).execute().use { it.isSuccessful }
        } catch (e: Exception) {
            false
        }
    }

    suspend fun uploadMedia(deviceId: String, token: String, file: File, lens: String, commandId: String?): Result<Long> = withContext(Dispatchers.IO) {
        try {
            val form = MultipartBody.Builder().setType(MultipartBody.FORM)
                .addFormDataPart("file", file.name, file.asRequestBody("image/jpeg".toMediaType()))
                .addFormDataPart("camera_lens", lens)
                .addFormDataPart("media_type", "photo")
            if (commandId != null) form.addFormDataPart("command_id", commandId)

            val req = Request.Builder()
                .url("${BuildConfig.API_BASE_URL}/api/v1/devices/$deviceId/media")
                .addHeader("Authorization", "Bearer $token")
                .post(form.build())
                .build()
            client.newCall(req).execute().use { resp ->
                val obj = JSONObject(resp.body?.string().orEmpty())
                if (!resp.isSuccessful || !obj.optBoolean("success", false)) {
                    return@withContext Result.failure(Exception(obj.optString("message", "Upload media gagal")))
                }
                Result.success(obj.getJSONObject("data").optLong("id"))
            }
        } catch (e: Exception) {
            Result.failure(e)
        }
    }
}
