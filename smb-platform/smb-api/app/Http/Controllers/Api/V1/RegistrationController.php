<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Device;
use App\Models\DeviceCredential;
use App\Models\DeviceRegistrationCode;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class RegistrationController extends Controller
{
    use ApiResponse;

    public function generate(Request $request)
    {
        $data = $request->validate([
            'site_id' => 'required|exists:sites,id',
            'team_id' => 'required|exists:teams,id',
        ]);

        $plain = strtoupper(Str::random(8));

        DeviceRegistrationCode::create([
            'code_hash' => hash('sha256', $plain),
            'site_id' => $data['site_id'],
            'team_id' => $data['team_id'],
            'created_by' => $request->user()?->id,
            'expires_at' => now()->addHours(24),
        ]);

        activity()->withProperties($data)->log('device.registration_code.generated');

        return $this->ok(['registration_code' => $plain, 'expires_at' => now()->addHours(24)->toDateTimeString()], 'Registration code dibuat.', 201);
    }

    public function register(Request $request)
    {
        $data = $request->validate([
            'registration_code' => 'required|string',
            'serial_number' => 'nullable|string|max:128',
        ]);

        $code = DeviceRegistrationCode::where('code_hash', hash('sha256', strtoupper($data['registration_code'])))
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->first();

        if (!$code) {
            activity()->log('device.registration.failed');
            return $this->fail('Registration code tidak valid, kedaluwarsa, atau sudah dipakai.', [], 422);
        }

        $device = Device::create([
            'site_id' => $code->site_id,
            'team_id' => $code->team_id,
            'serial_number' => $data['serial_number'] ?? null,
        ]);

        $plainToken = bin2hex(random_bytes(32));
        DeviceCredential::create([
            'device_id' => $device->id,
            'credential_hash' => hash('sha256', $plainToken),
            'token' => hash('sha256', $plainToken),
        ]);

        $code->update(['used_at' => now(), 'used_by_device_id' => $device->id]);

        activity()->withProperties(['device_id' => $device->id])->log('device.registered');

        return $this->ok(['device_id' => $device->id, 'token' => $plainToken], 'Device terdaftar.', 201);
    }
}
