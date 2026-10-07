<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Device;
use App\Models\DeviceCommand;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CommandController extends Controller
{
    use ApiResponse;

    public function store(Request $request, Device $device)
    {
        $data = $request->validate([
            'command_type' => 'required|string|in:lock,unlock,location_request,camera_request,sync',
            'payload' => 'nullable|array',
            'expires_in' => 'nullable|integer|min:30|max:86400',
            'idempotency_key' => 'required|string|max:128',
        ]);

        $existing = DeviceCommand::where('idempotency_key', $data['idempotency_key'])->first();
        if ($existing) {
            return $this->ok($existing, 'Command sudah terdaftar (idempotent).', 200);
        }

        $command = DeviceCommand::create([
            'id' => (string) Str::uuid(),
            'device_id' => $device->id,
            'command_type' => $data['command_type'],
            'idempotency_key' => $data['idempotency_key'],
            'created_by' => $request->user()?->id,
            'status' => 'QUEUED',
            'payload' => $data['payload'] ?? null,
            'expires_at' => now()->addSeconds($data['expires_in'] ?? 300),
        ]);

        $command->transitionTo('QUEUED', 'Created', $request->user()?->email);
        activity()->withProperties(['device_id' => $device->id])->withProperties(['command_id' => $command->id])->log('device.command.created');

        return $this->ok($command, 'Command dibuat.', 201);
    }

    public function index(Device $device)
    {
        $commands = $device->commands()->orderByDesc('created_at')->paginate(request()->integer('per_page', 15));

        return $this->ok([
            'items' => $commands->items(),
            'total' => $commands->total(),
            'current_page' => $commands->currentPage(),
            'last_page' => $commands->lastPage(),
            'per_page' => $commands->perPage(),
        ]);
    }

    public function pending(Device $device)
    {
        $commands = $device->commands()
            ->whereIn('status', ['QUEUED', 'SENT'])
            ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))
            ->orderBy('created_at')
            ->get(['id', 'device_id', 'command_type', 'payload', 'expires_at', 'status']);

        return $this->ok($commands);
    }

    public function ack(Request $request, DeviceCommand $command)
    {
        $data = $request->validate([
            'status' => 'required|string|in:RECEIVED,EXECUTING,SUCCESS,FAILED,DELIVERED',
            'result' => 'nullable|string',
            'error_message' => 'nullable|string',
        ]);

        if ($command->status === 'SUCCESS' || $command->status === 'FAILED' || $command->status === 'EXPIRED' || $command->status === 'CANCELLED') {
            return $this->fail('Command sudah terminal, tidak dapat diperbarui.', [], 409);
        }

        $command->update([
            'result' => $data['result'] ?? $command->result,
            'error_message' => $data['error_message'] ?? null,
            'received_at' => $data['status'] === 'RECEIVED' ? now() : $command->received_at,
            'executed_at' => $data['status'] === 'EXECUTING' ? now() : $command->executed_at,
            'completed_at' => in_array($data['status'], ['SUCCESS', 'FAILED'], true) ? now() : null,
        ]);
        $command->transitionTo($data['status'], $data['error_message'] ?? null, 'device');

        return $this->ok($command, 'Command acknowldeged.');
    }
}
