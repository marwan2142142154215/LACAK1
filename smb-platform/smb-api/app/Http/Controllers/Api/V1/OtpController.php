<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Device;
use App\Models\DeviceOtp;
use App\Support\ApiResponse;
use Illuminate\Http\Request;

class OtpController extends Controller
{
    use ApiResponse;

    public function generate(Request $request, Device $device)
    {
        $request->validate(['expires_in' => 'nullable|integer|min:60|max:3600']);

        // invalidate all previous OTPs
        $device->otps()->whereNull('used_at')->update(['used_at' => now()]);

        $plain = (string) random_int(100000, 999999);
        DeviceOtp::create([
            'device_id' => $device->id,
            'otp_hash' => hash('sha256', $plain),
            'attempts' => 0,
            'expires_at' => now()->addSeconds($request->input('expires_in', 300)),
            'created_by' => $request->user()?->id,
        ]);

        activity()->withProperties(['device_id' => $device->id])->log('otp.generated');

        return $this->ok(['otp' => $plain, 'expires_in' => (int) $request->input('expires_in', 300)], 'OTP dibuat.', 201);
    }

    public function verify(Request $request, Device $device)
    {
        $request->validate(['otp' => 'required|digits:6']);

        $otp = $device->otps()
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->latest()
            ->first();

        if (!$otp) {
            return $this->fail('OTP tidak ditemukan atau kedaluwarsa.', [], 422);
        }

        if ($otp->attempts >= 5) {
            $otp->update(['used_at' => now()]);
            activity()->withProperties(['device_id' => $device->id])->log('otp.bruteforce.blocked');
            return $this->fail('OTP diblokir karena terlalu banyak percobaan.', [], 429);
        }

        $otp->increment('attempts');

        if (!hash_equals($otp->otp_hash, hash('sha256', $request->input('otp')))) {
            activity()->withProperties(['device_id' => $device->id])->log('otp.verify.failed');
            return $this->fail('OTP salah.', [], 422);
        }

        $otp->update(['used_at' => now()]);
        activity()->withProperties(['device_id' => $device->id])->log('otp.verify.success');

        return $this->ok(null, 'OTP valid.');
    }
}
