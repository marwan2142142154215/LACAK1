package com.smb.tracker.data

import android.annotation.SuppressLint
import android.content.Context
import android.content.pm.PackageManager
import android.location.Location
import androidx.core.content.ContextCompat
import com.google.android.gms.location.FusedLocationProviderClient
import com.google.android.gms.location.LocationServices
import kotlinx.coroutines.tasks.await

object LocationHelper {

    fun hasPermission(context: Context): Boolean =
        ContextCompat.checkSelfPermission(context, android.Manifest.permission.ACCESS_FINE_LOCATION) == PackageManager.PERMISSION_GRANTED

    @SuppressLint("MissingPermission")
    suspend fun lastLocation(context: Context): Location? {
        if (!hasPermission(context)) return null
        return try {
            val client: FusedLocationProviderClient = LocationServices.getFusedLocationProviderClient(context)
            client.lastLocation.await()
        } catch (e: Exception) {
            null
        }
    }
}
