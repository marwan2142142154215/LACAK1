<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CommandController;
use App\Http\Controllers\Api\V1\DeviceController;
use App\Http\Controllers\Api\V1\HealthController;
use App\Http\Controllers\Api\V1\HeartbeatController;
use App\Http\Controllers\Api\V1\LocationController;
use App\Http\Controllers\Api\V1\RegistrationController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::get('health', [HealthController::class, 'index']);

    // Device-authenticated endpoints
    Route::middleware('device.auth')->group(function () {
        Route::post('devices/{device}/heartbeat', [HeartbeatController::class, 'store']);
        Route::post('commands/{command}/ack', [CommandController::class, 'ack']);
    });

    // Public device registration (uses registration code)
    Route::post('devices/register', [RegistrationController::class, 'register']);

    Route::post('auth/login', [AuthController::class, 'login']);

    // Admin-protected endpoints (Sanctum + permission middleware added later)
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('auth/logout', [AuthController::class, 'logout']);
        Route::get('auth/me', [AuthController::class, 'me']);

        Route::get('devices', [DeviceController::class, 'index']);
        Route::get('devices/{device}', [DeviceController::class, 'show']);
        Route::patch('devices/{device}', [DeviceController::class, 'update']);
        Route::delete('devices/{device}', [DeviceController::class, 'destroy']);

        Route::post('devices/{device}/commands', [CommandController::class, 'store']);
        Route::get('devices/{device}/commands', [CommandController::class, 'index']);

        Route::get('devices/{device}/locations', [LocationController::class, 'index']);
        Route::get('devices/{device}/locations/latest', [LocationController::class, 'latest']);

        Route::post('registration-codes', [RegistrationController::class, 'generate']);
    });
});
