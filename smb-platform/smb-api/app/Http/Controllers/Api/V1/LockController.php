<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Device;
use App\Models\DeviceCommand;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class LockController extends Controller
{
    use ApiResponse;

    public function lock(Request $request, Device $device)
    {
        $command = DeviceCommand::create([
            'id' => (string) Str::uuid(),
            'device_id' => $device->id,
            'command_type' => 'lock',
            'idempotency_key' => (string) Str::uuid(),
            'created_by' => $request->user()?->id,
            'status' => 'QUEUED',
            'expires_at' => now()->addMinutes(30),
        ]);
        $command->transitionTo('QUEUED', 'device locked by admin', $request->user()?->email);
        activity()->withProperties(['device_id' => $device->id, 'command_id' => $command->id])->log('device.lock');

        return $this->ok($command, 'Perintah kunci dikirim.', 201);
    }

    public function unlock(Request $request, Device $device)
    {
        $command = DeviceCommand::create([
            'id' => (string) Str::uuid(),
            'device_id' => $device->id,
            'command_type' => 'unlock',
            'idempotency_key' => (string) Str::uuid(),
            'created_by' => $request->user()?->id,
            'status' => 'QUEUED',
            'expires_at' => now()->addMinutes(30),
        ]);
        $command->transitionTo('QUEUED', 'device unlocked by admin', $request->user()?->email);
        activity()->withProperties(['device_id' => $device->id, 'command_id' => $command->id])->log('device.unlock');

        return $this->ok($command, 'Perintah buka kunci dikirim.', 201);
    }
}
