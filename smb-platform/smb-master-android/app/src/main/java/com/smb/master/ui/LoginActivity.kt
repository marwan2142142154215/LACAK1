package com.smb.master.ui

import android.content.Intent
import android.os.Bundle
import android.widget.Button
import android.widget.EditText
import android.widget.TextView
import androidx.appcompat.app.AppCompatActivity
import androidx.lifecycle.lifecycleScope
import com.smb.master.R
import com.smb.master.data.MasterApiClient
import kotlinx.coroutines.launch

class LoginActivity : AppCompatActivity() {
    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        setContentView(R.layout.activity_login)

        val email = findViewById<EditText>(R.id.edtEmail)
        val password = findViewById<EditText>(R.id.edtPassword)
        val btn = findViewById<Button>(R.id.btnLogin)
        val status = findViewById<TextView>(R.id.txtStatus)

        btn.setOnClickListener {
            btn.isEnabled = false
            lifecycleScope.launch {
                val result = MasterApiClient.login(email.text.toString(), password.text.toString())
                btn.isEnabled = true
                if (result.isSuccess) {
                    startActivity(Intent(this@LoginActivity, DeviceListActivity::class.java).putExtra("token", result.getOrNull()))
                } else {
                    status.text = result.exceptionOrNull()?.message
                }
            }
        }
    }
}
