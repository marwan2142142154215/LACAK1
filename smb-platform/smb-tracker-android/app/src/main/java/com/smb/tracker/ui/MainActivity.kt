package com.smb.tracker.ui

import android.Manifest
import android.content.Intent
import android.content.pm.PackageManager
import android.os.Build
import android.os.Bundle
import android.widget.Button
import android.widget.EditText
import android.widget.TextView
import androidx.appcompat.app.AppCompatActivity
import androidx.core.app.ActivityCompat
import androidx.core.content.ContextCompat
import androidx.lifecycle.lifecycleScope
import com.smb.tracker.R
import com.smb.tracker.data.CameraController
import com.smb.tracker.data.DeviceApiClient
import com.smb.tracker.data.DeviceStore
import com.smb.tracker.service.TrackingService
import kotlinx.coroutines.flow.first
import kotlinx.coroutines.launch

class MainActivity : AppCompatActivity() {

    private lateinit var store: DeviceStore

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        setContentView(R.layout.activity_main)
        store = DeviceStore(this)

        requestRuntimePermissions()

        val codeInput = findViewById<EditText>(R.id.edtCode)
        val btnRegister = findViewById<Button>(R.id.btnRegister)
        val btnStart = findViewById<Button>(R.id.btnStart)
        val status = findViewById<TextView>(R.id.txtStatus)

        lifecycleScope.launch {
            val id = store.deviceId.first()
            if (id != null) {
                status.text = "Perangkat terdaftar: $id"
                startServiceCompat()
            }
        }

        btnRegister.setOnClickListener {
            val code = codeInput.text.toString().trim()
            if (code.isEmpty()) {
                status.text = "Masukkan registration code"
                return@setOnClickListener
            }
            btnRegister.isEnabled = false
            lifecycleScope.launch {
                val result = DeviceApiClient.register(code)
                btnRegister.isEnabled = true
                if (result.isSuccess) {
                    val pair = result.getOrNull()!!
                    store.save(pair.first, pair.second)
                    status.text = "Registrasi sukses. Device ID: ${pair.first}"
                    startServiceCompat()
                } else {
                    status.text = "Registrasi gagal: ${result.exceptionOrNull()?.message}"
                }
            }
        }

        btnStart.setOnClickListener { startServiceCompat() }
    }

    private fun startServiceCompat() {
        val i = Intent(this, TrackingService::class.java)
        if (android.os.Build.VERSION.SDK_INT >= 26) startForegroundService(i) else startService(i)
    }

    override fun onStart() {
        super.onStart()
        CameraController.attach(this)
    }

    override fun onStop() {
        CameraController.detach()
        super.onStop()
    }

    private fun requestRuntimePermissions() {
        val needed = mutableListOf(
            Manifest.permission.CAMERA,
            Manifest.permission.ACCESS_FINE_LOCATION,
            Manifest.permission.ACCESS_COARSE_LOCATION,
        )
        if (Build.VERSION.SDK_INT >= 33) needed.add(Manifest.permission.POST_NOTIFICATIONS)

        val missing = needed.filter {
            ContextCompat.checkSelfPermission(this, it) != PackageManager.PERMISSION_GRANTED
        }
        if (missing.isNotEmpty()) {
            ActivityCompat.requestPermissions(this, missing.toTypedArray(), 100)
        }
    }
}
