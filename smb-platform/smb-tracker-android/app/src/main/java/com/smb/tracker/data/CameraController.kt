package com.smb.tracker.data

import android.content.Context
import android.content.pm.PackageManager
import android.util.Log
import androidx.camera.core.CameraSelector
import androidx.camera.core.ImageCapture
import androidx.camera.core.ImageCaptureException
import androidx.camera.lifecycle.ProcessCameraProvider
import androidx.core.content.ContextCompat
import androidx.lifecycle.LifecycleOwner
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.suspendCancellableCoroutine
import kotlinx.coroutines.withContext
import java.io.File
import java.lang.ref.WeakReference
import kotlin.coroutines.resume

/**
 * Real camera capture via CameraX. Capture is only possible while a foreground
 * Activity is attached: modern Android blocks background camera access. When the
 * app is not in the foreground (or permission is missing) we return an explicit
 * Failure reason instead of pretending the capture succeeded.
 */
object CameraController {

    @Volatile
    private var ownerRef: WeakReference<LifecycleOwner>? = null

    fun attach(owner: LifecycleOwner) {
        ownerRef = WeakReference(owner)
    }

    fun detach() {
        ownerRef = null
    }

    fun hasPermission(context: Context): Boolean =
        ContextCompat.checkSelfPermission(context, android.Manifest.permission.CAMERA) == PackageManager.PERMISSION_GRANTED

    sealed class CaptureResult {
        data class Success(val file: File) : CaptureResult()
        data class Failure(val reason: String) : CaptureResult()
    }

    suspend fun capture(context: Context, lens: String): CaptureResult {
        if (!hasPermission(context)) {
            return CaptureResult.Failure("Izin kamera belum diberikan pada perangkat")
        }

        val owner = ownerRef?.get()
            ?: return CaptureResult.Failure("Aplikasi harus berada di depan untuk menangkap gambar (background camera dibatasi Android)")

        return try {
            val provider = withContext(Dispatchers.IO) { ProcessCameraProvider.getInstance(context).get() }
            val selector = if (lens.equals("front", ignoreCase = true)) {
                CameraSelector.DEFAULT_FRONT_CAMERA
            } else {
                CameraSelector.DEFAULT_BACK_CAMERA
            }
            val imageCapture = ImageCapture.Builder()
                .setCaptureMode(ImageCapture.CAPTURE_MODE_MINIMIZE_LATENCY)
                .build()

            withContext(Dispatchers.Main) {
                provider.unbindAll()
                provider.bindToLifecycle(owner, selector, imageCapture)
            }

            val file = File(context.cacheDir, "smb_capture_${System.currentTimeMillis()}.jpg")

            val result = suspendCancellableCoroutine { cont ->
                imageCapture.takePicture(
                    ImageCapture.OutputFileOptions.Builder(file).build(),
                    ContextCompat.getMainExecutor(context),
                    object : ImageCapture.OnImageSavedCallback {
                        override fun onError(exception: ImageCaptureException) {
                            if (cont.isActive) {
                                cont.resume(CaptureResult.Failure("Gagal menangkap gambar: ${exception.message}"))
                            }
                        }

                        override fun onImageSaved(outputFileResults: ImageCapture.OutputFileResults) {
                            if (cont.isActive) {
                                cont.resume(CaptureResult.Success(file))
                            }
                        }
                    },
                )
            }

            withContext(Dispatchers.Main) { provider.unbindAll() }
            result
        } catch (e: Exception) {
            Log.w("SMB-CAM", "capture failed", e)
            CaptureResult.Failure("Kamera tidak tersedia: ${e.message}")
        }
    }
}
