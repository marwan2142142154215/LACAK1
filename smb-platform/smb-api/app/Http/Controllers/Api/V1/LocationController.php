<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Device;
use App\Models\DeviceLocation;
use App\Support\ApiResponse;
use Illuminate\Http\Request;

class LocationController extends Controller
{
    use ApiResponse;

    public function index(Device $device)
    {
        $locations = $device->locations()->orderByDesc('recorded_at')->paginate(request()->integer('per_page', 50));

        return $this->ok([
            'items' => $locations->items(),
            'total' => $locations->total(),
            'current_page' => $locations->currentPage(),
            'last_page' => $locations->lastPage(),
            'per_page' => $locations->perPage(),
        ]);
    }

    public function latest(Device $device)
    {
        return $this->ok($device->locations()->latest('recorded_at')->first());
    }

    public function requestNow(Request $request, Device $device)
    {
        $command = \App\Models\DeviceCommand::create([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'device_id' => $device->id,
            'command_type' => 'location_request',
            'idempotency_key' => (string) \Illuminate\Support\Str::uuid(),
            'created_by' => $request->user()?->id,
            'status' => 'QUEUED',
            'expires_at' => now()->addMinutes(10),
        ]);
        $command->transitionTo('QUEUED', 'location request', $request->user()?->email);
        activity()->withProperties(['device_id' => $device->id])->log('device.location.requested');

        return $this->ok($command, 'Permintaan lokasi dikirim.', 201);
    }

    public function requestCamera(Request $request, Device $device)
    {
        $data = $request->validate(['lens' => 'required|in:front,back']);

        $command = \App\Models\DeviceCommand::create([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'device_id' => $device->id,
            'command_type' => 'camera_request',
            'idempotency_key' => (string) \Illuminate\Support\Str::uuid(),
            'created_by' => $request->user()?->id,
            'status' => 'QUEUED',
            'payload' => ['lens' => $data['lens']],
            'expires_at' => now()->addMinutes(10),
        ]);
        $command->transitionTo('QUEUED', 'camera request '.$data['lens'], $request->user()?->email);
        activity()->withProperties(['device_id' => $device->id, 'lens' => $data['lens']])->log('device.camera.requested');

        return $this->ok($command, 'Permintaan kamera dikirim.', 201);
    }

    public function mapGeoJson()
    {
        $devices = Device::with(['site', 'team'])->whereNotNull('last_seen_at')->get();
        $features = $devices->map(function (Device $d) {
            $latest = $d->locations()->latest('recorded_at')->first();
            return [
                'type' => 'Feature',
                'geometry' => $latest ? ['type' => 'Point', 'coordinates' => [(float) $latest->longitude, (float) $latest->latitude]] : null,
                'properties' => [
                    'device_id' => $d->id,
                    'name' => $d->name,
                    'status' => $d->status,
                    'battery_level' => $d->battery_level,
                    'network_type' => $d->network_type,
                    'last_seen_at' => optional($d->last_seen_at)->toDateTimeString(),
                    'site' => $d->site?->name,
                    'team' => $d->team?->name,
                    'accuracy' => $latest?->accuracy,
                    'recorded_at' => optional($latest?->recorded_at)->toDateTimeString(),
                ],
            ];
        });

        return $this->ok(['type' => 'FeatureCollection', 'features' => $features]);
    }
}
