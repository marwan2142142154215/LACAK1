package com.smb.master.ui

import android.content.Intent
import android.os.Bundle
import android.widget.ArrayAdapter
import android.widget.Button
import android.widget.ListView
import android.widget.TextView
import androidx.appcompat.app.AppCompatActivity
import androidx.lifecycle.lifecycleScope
import com.smb.master.R
import com.smb.master.data.MasterApiClient
import kotlinx.coroutines.launch

class DeviceListActivity : AppCompatActivity() {
    private lateinit var token: String
    private lateinit var adapter: ArrayAdapter<String>

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        setContentView(R.layout.activity_device_list)
        token = intent.getStringExtra("token") ?: ""

        val list = findViewById<ListView>(R.id.listDevices)
        val status = findViewById<TextView>(R.id.txtStatus)
        val btnRefresh = findViewById<Button>(R.id.btnRefresh)

        adapter = ArrayAdapter(this, android.R.layout.simple_list_item_1, mutableListOf())
        list.adapter = adapter

        btnRefresh.setOnClickListener { load(status) }

        list.setOnItemClickListener { _, _, position, _ ->
            val line = adapter.getItem(position) ?: return@setOnItemClickListener
            val id = line.substringBefore('|').trim()
            startActivity(Intent(this, DeviceDetailActivity::class.java).putExtra("token", token).putExtra("device_id", id))
        }

        load(status)
    }

    private fun load(status: TextView) {
        lifecycleScope.launch {
            status.text = "Memuat..."
            val items = MasterApiClient.devices(token)
            adapter.clear()
            adapter.addAll(items)
            status.text = "${items.size} device"
        }
    }
}
