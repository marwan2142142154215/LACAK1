package com.smb.master.ui

import android.os.Bundle
import android.widget.Button
import android.widget.TextView
import androidx.appcompat.app.AppCompatActivity
import androidx.lifecycle.lifecycleScope
import com.smb.master.R
import com.smb.master.data.MasterApiClient
import kotlinx.coroutines.launch

class DeviceDetailActivity : AppCompatActivity() {
    private lateinit var token: String
    private lateinit var deviceId: String
    private lateinit var status: TextView

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        setContentView(R.layout.activity_device_detail)
        token = intent.getStringExtra("token") ?: ""
        deviceId = intent.getStringExtra("device_id") ?: ""
        title = "Device: $deviceId"

        status = findViewById(R.id.txtStatus)
        bind(R.id.btnLock, "Lock") { MasterApiClient.action(token, deviceId, "lock") }
        bind(R.id.btnUnlock, "Unlock") { MasterApiClient.action(token, deviceId, "unlock") }
        bind(R.id.btnLocation, "Locasi") { MasterApiClient.action(token, deviceId, "location/request") }
        bind(R.id.btnCameraFront, "Kamera Depan") { MasterApiClient.requestCamera(token, deviceId, "front") }
        bind(R.id.btnCameraBack, "Kamera Belakang") { MasterApiClient.requestCamera(token, deviceId, "back") }

        findViewById<Button>(R.id.btnOtp).setOnClickListener {
            lifecycleScope.launch {
                val otp = MasterApiClient.generateOtp(token, deviceId)
                status.text = if (otp != null) "OTP: $otp" else "Gagal generate OTP"
            }
        }
    }

    private fun bind(id: Int, label: String, op: suspend () -> Boolean) {
        findViewById<Button>(id).text = label
        findViewById<Button>(id).setOnClickListener {
            lifecycleScope.launch {
                val ok = op()
                status.text = if (ok) "$label terkirim" else "$label gagal"
            }
        }
    }
}
