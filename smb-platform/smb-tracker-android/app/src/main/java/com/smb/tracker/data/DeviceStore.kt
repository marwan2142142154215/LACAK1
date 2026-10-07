package com.smb.tracker.data

import android.content.Context
import androidx.datastore.preferences.core.edit
import androidx.datastore.preferences.core.stringPreferencesKey
import androidx.datastore.preferences.preferencesDataStore
import kotlinx.coroutines.flow.Flow
import kotlinx.coroutines.flow.map

private val Context.dataStore by preferencesDataStore(name = "device_prefs")

object PrefKeys {
    val DEVICE_ID = stringPreferencesKey("device_id")
    val TOKEN = stringPreferencesKey("token")
}

class DeviceStore(private val context: Context) {
    val deviceId: Flow<String?> = context.dataStore.data.map { it[PrefKeys.DEVICE_ID] }
    val token: Flow<String?> = context.dataStore.data.map { it[PrefKeys.TOKEN] }

    suspend fun save(deviceId: String, token: String) {
        context.dataStore.edit {
            it[PrefKeys.DEVICE_ID] = deviceId
            it[PrefKeys.TOKEN] = token
        }
    }
}
