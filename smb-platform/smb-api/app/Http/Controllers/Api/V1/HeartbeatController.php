<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Device;
use App\Models\DeviceHeartbeat;
use App\Models\DeviceLocation;
use App\Support\ApiResponse;
use Illuminate\Http\Request;

class HeartbeatController extends Controller
{
    use ApiResponse;

    public function store(Request $request, Device $device)
    {
        $data = $request->validate([
            'battery_level' => 'nullable|integer|min:0|max:100',
            'network_type' => 'nullable|string|max:32',
            'connection_state' => 'nullable|string|max:32',
            'app_version' => 'nullable|string|max:64',
            'android_api' => 'nullable|integer|min:26|max:36',
            'last_location_at' => 'nullable|date',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'accuracy' => 'nullable|numeric|min:0',
            'source' => 'nullable|string|max:32',
        ]);

        DeviceHeartbeat::create(array_merge($data, ['device_id' => $device->id, 'recorded_at' => now()]));

        if (isset($data['latitude']) && isset($data['longitude'])) {
            DeviceLocation::create([
                'device_id' => $device->id,
                'latitude' => $data['latitude'],
                'longitude' => $data['longitude'],
                'accuracy' => $data['accuracy'] ?? null,
                'source' => $data['source'] ?? 'heartbeat',
                'battery_level' => $data['battery_level'] ?? null,
                'network_type' => $data['network_type'] ?? null,
                'recorded_at' => now(),
            ]);
        }

        $device->update([
            'last_seen_at' => now(),
            'battery_level' => $data['battery_level'] ?? $device->battery_level,
            'network_type' => $data['network_type'] ?? $device->network_type,
            'android_api' => $data['android_api'] ?? $device->android_api,
            'app_version' => $data['app_version'] ?? $device->app_version,
            'connection_state' => $data['connection_state'] ?? $device->connection_state,
            'status' => 'ONLINE',
        ]);

        return $this->ok(null, 'Heartbeat diterima.');
    }
}
