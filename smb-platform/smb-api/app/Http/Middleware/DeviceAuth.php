<?php

namespace App\Http\Middleware;

use App\Models\DeviceCredential;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DeviceAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        $deviceParam = $request->route('device');
        $deviceId = $deviceParam instanceof \App\Models\Device ? $deviceParam->id : ($deviceParam ?? $request->input('device_id'));
        $token = $request->bearerToken();

        if (!$deviceId || !$token) {
            return response()->json(['success' => false, 'message' => 'Device authentication required.', 'errors' => (object) []], 401);
        }

        $credential = DeviceCredential::where('device_id', $deviceId)
            ->where('token', hash('sha256', $token))
            ->whereNull('revoked_at')
            ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))
            ->first();

        if (!$credential) {
            return response()->json(['success' => false, 'message' => 'Invalid device credential.', 'errors' => (object) []], 401);
        }

        $request->attributes->set('device_id', $deviceId);

        return $next($request);
    }
}
