<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Device;
use App\Support\ApiResponse;
use Illuminate\Http\Request;

class DeviceController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $devices = Device::with(['site', 'team'])
            ->when($request->site_id, fn ($q, $v) => $q->where('site_id', $v))
            ->when($request->team_id, fn ($q, $v) => $q->where('team_id', $v))
            ->when($request->status, fn ($q, $v) => $q->where('status', $v))
            ->when($request->android_version, fn ($q, $v) => $q->where('android_version', $v))
            ->orderByDesc('last_seen_at')
            ->paginate($request->integer('per_page', 15));

        return $this->ok([
            'items' => $devices->items(),
            'total' => $devices->total(),
            'current_page' => $devices->currentPage(),
            'last_page' => $devices->lastPage(),
            'per_page' => $devices->perPage(),
        ]);
    }

    public function show(Device $device)
    {
        return $this->ok($device->load(['site', 'team', 'policies']));
    }

    public function update(Request $request, Device $device)
    {
        $device->update($request->validate([
            'name' => 'sometimes|string|max:255',
            'site_id' => 'sometimes|exists:sites,id',
            'team_id' => 'sometimes|exists:teams,id',
        ]));

        activity()->withProperties(["device_id" => $device->id])->log('device.updated');

        return $this->ok($device, 'Device diperbarui.');
    }

    public function destroy(Device $device)
    {
        $device->delete();
        activity()->withProperties(["device_id" => $device->id])->log('device.deleted');

        return $this->ok(null, 'Device dihapus.');
    }
}
