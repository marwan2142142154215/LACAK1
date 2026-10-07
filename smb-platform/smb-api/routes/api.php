<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CommandController;
use App\Http\Controllers\Api\V1\DeviceController;
use App\Http\Controllers\Api\V1\HealthController;
use App\Http\Controllers\Api\V1\HeartbeatController;
use App\Http\Controllers\Api\V1\LocationController;
use App\Http\Controllers\Api\V1\OtpController;
use App\Http\Controllers\Api\V1\RegistrationController;
use App\Http\Controllers\Api\V1\LockController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::get('health', [HealthController::class, 'index']);

    // Device-authenticated endpoints
    Route::middleware('device.auth')->group(function () {
        Route::post('devices/{device}/heartbeat', [HeartbeatController::class, 'store']);
        Route::get('devices/{device}/commands/pending', [CommandController::class, 'pending']);
        Route::post('devices/{device}/verify-credential', fn () => response()->json(['success' => true, 'message' => 'OK', 'data' => ['verified' => true]]));
        Route::post('commands/{command}/ack', [CommandController::class, 'ack']);
        Route::post('devices/{device}/otp/verify', [OtpController::class, 'verify']);
    });

    // Public device registration (uses registration code)
    Route::post('devices/register', [RegistrationController::class, 'register'])->middleware('throttle:5,1');

    Route::post('auth/login', [AuthController::class, 'login'])->middleware('throttle:5,1');

    // Admin-protected endpoints (Sanctum + permission middleware added later)
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('auth/logout', [AuthController::class, 'logout']);
        Route::get('auth/me', [AuthController::class, 'me']);
        Route::post('auth/2fa/enable', [AuthController::class, 'twoFactorEnable']);
        Route::post('auth/2fa/verify', [AuthController::class, 'twoFactorVerify']);
        Route::delete('auth/2fa', [AuthController::class, 'twoFactorDisable']);

        Route::get('devices', [DeviceController::class, 'index'])->middleware('permission:devices.view');
        Route::get('devices/{device}', [DeviceController::class, 'show'])->middleware('permission:devices.view');
        Route::patch('devices/{device}', [DeviceController::class, 'update'])->middleware('permission:devices.update');
        Route::delete('devices/{device}', [DeviceController::class, 'destroy'])->middleware('permission:devices.delete');

        Route::post('devices/{device}/commands', [CommandController::class, 'store'])->middleware('permission:devices.command');
        Route::get('devices/{device}/commands', [CommandController::class, 'index'])->middleware('permission:devices.view');

        Route::get('devices/{device}/locations', [LocationController::class, 'index'])->middleware('permission:devices.location');
        Route::get('devices/{device}/locations/latest', [LocationController::class, 'latest'])->middleware('permission:devices.location');

        Route::post('devices/{device}/otp', [OtpController::class, 'generate'])->middleware('permission:devices.unlock');

        Route::post('devices/{device}/lock', [LockController::class, 'lock'])->middleware('permission:devices.lock');
        Route::post('devices/{device}/unlock', [LockController::class, 'unlock'])->middleware('permission:devices.unlock');

        Route::post('registration-codes', [RegistrationController::class, 'generate'])->middleware('permission:devices.create');
    });
});
