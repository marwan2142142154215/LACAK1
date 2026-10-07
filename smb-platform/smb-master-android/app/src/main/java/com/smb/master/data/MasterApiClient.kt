package com.smb.master.data

import com.smb.master.BuildConfig
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.withContext
import okhttp3.MediaType.Companion.toMediaType
import okhttp3.OkHttpClient
import okhttp3.Request
import okhttp3.RequestBody.Companion.toRequestBody
import org.json.JSONObject

object MasterApiClient {
    private val client = OkHttpClient()
    val json = "application/json; charset=utf-8".toMediaType()

    suspend fun login(email: String, password: String): Result<String> = withContext(Dispatchers.IO) {
        try {
            val body = JSONObject()
                .put("email", email)
                .put("password", password)
                .put("device_name", "smb-master")
                .toString()
                .toRequestBody(json)
            val req = Request.Builder()
                .url(BuildConfig.API_BASE_URL + "/api/v1/auth/login")
                .post(body)
                .build()
            client.newCall(req).execute().use { resp ->
                val text = resp.body?.string().orEmpty()
                val obj = JSONObject(text)
                if (!resp.isSuccessful || !obj.optBoolean("success", false)) {
                    return@withContext Result.failure(Exception(obj.optString("message", "Login gagal")))
                }
                Result.success(obj.getJSONObject("data").getString("access_token"))
            }
        } catch (e: Exception) {
            Result.failure(e)
        }
    }

    suspend fun devices(token: String): List<String> = withContext(Dispatchers.IO) {
        try {
            val req = Request.Builder()
                .url(BuildConfig.API_BASE_URL + "/api/v1/devices?per_page=50")
                .addHeader("Authorization", "Bearer $token")
                .get()
                .build()
            val out = mutableListOf<String>()
            client.newCall(req).execute().use { resp ->
                val obj = JSONObject(resp.body?.string().orEmpty())
                val data = obj.optJSONObject("data") ?: return@withContext out
                val items = data.optJSONArray("items") ?: return@withContext out
                for (i in 0 until items.length()) {
                    val d = items.getJSONObject(i)
                    out.add(d.optString("id") + " | " + d.optString("name") + " | " + d.optString("status"))
                }
            }
            out
        } catch (e: Exception) {
            emptyList()
        }
    }

    suspend fun action(token: String, deviceId: String, action: String): Boolean = withContext(Dispatchers.IO) {
        try {
            val req = Request.Builder()
                .url(BuildConfig.API_BASE_URL + "/api/v1/devices/$deviceId/$action")
                .addHeader("Authorization", "Bearer $token")
                .post("{}".toRequestBody(json))
                .build()
            client.newCall(req).execute().use { it.isSuccessful }
        } catch (e: Exception) {
            false
        }
    }

    suspend fun requestCamera(token: String, deviceId: String, lens: String): Boolean = withContext(Dispatchers.IO) {
        try {
            val body = JSONObject().put("lens", lens).toString().toRequestBody(json)
            val req = Request.Builder()
                .url(BuildConfig.API_BASE_URL + "/api/v1/devices/$deviceId/camera/request")
                .addHeader("Authorization", "Bearer $token")
                .post(body)
                .build()
            client.newCall(req).execute().use { it.isSuccessful }
        } catch (e: Exception) {
            false
        }
    }

    suspend fun generateOtp(token: String, deviceId: String): String? = withContext(Dispatchers.IO) {
        try {
            val req = Request.Builder()
                .url(BuildConfig.API_BASE_URL + "/api/v1/devices/$deviceId/otp")
                .addHeader("Authorization", "Bearer $token")
                .post("{}".toRequestBody(json))
                .build()
            client.newCall(req).execute().use { resp ->
                val obj = JSONObject(resp.body?.string().orEmpty())
                obj.optJSONObject("data")?.optString("otp")
            }
        } catch (e: Exception) {
            null
        }
    }
}
