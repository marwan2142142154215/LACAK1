<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    use ApiResponse;

    public function login(Request $request)
    {
        $data = $request->validate([
            'email' => 'required|string',
            'password' => 'required|string',
            'device_name' => 'required|string|max:128',
        ]);

        $identifier = $data['email'];
        $user = User::where('email', $identifier)
            ->orWhere('name', $identifier)
            ->first();

        if (!$user || !Hash::check($data['password'], $user->password)) {
            activity()->withProperties(['email' => $identifier])->log('auth.login.failed');
            return $this->fail('Kredensial tidak valid.', [], 401);
        }

        if ($user->two_factor_enabled) {
            $data2 = $request->validate(['two_factor_code' => 'required|digits:6']);
            $google2fa = new \PragmaRX\Google2FA\Google2FA();
            if (!$google2fa->verifyKey($user->two_factor_secret, $data2['two_factor_code'])) {
                activity()->log('2fa.login.failed');
                return $this->fail('Kode 2FA tidak valid.', [], 401);
            }
        }

        $token = $user->createToken($data['device_name'])->plainTextToken;
        activity()->performedOn($user)->log('auth.login');

        return $this->ok([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $user,
            'roles' => $user->getRoleNames(),
            'permissions' => $user->getAllPermissions()->pluck('name'),
        ], 'Login berhasil.');
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        activity()->performedOn($request->user())->log('auth.logout');

        return $this->ok(null, 'Logout berhasil.');
    }

    public function me(Request $request)
    {
        return $this->ok($request->user()->load('roles'));
    }

    public function twoFactorEnable(Request $request)
    {
        $google2fa = new \PragmaRX\Google2FA\Google2FA();
        $user = $request->user();
        $user->two_factor_secret = $google2fa->generateSecretKey();
        $user->save();

        $qrUrl = $google2fa->getQRCodeUrl(config('app.name'), $user->email, $user->two_factor_secret);
        activity()->performedOn($user)->log('2fa.enabled.setup');

        return $this->ok(['secret' => $user->two_factor_secret, 'qr_url' => $qrUrl], 'Scan QR di aplikasi authenticator.');
    }

    public function twoFactorVerify(Request $request)
    {
        $data = $request->validate(['code' => 'required|digits:6']);
        $google2fa = new \PragmaRX\Google2FA\Google2FA();
        $user = $request->user();

        if (!$user->two_factor_secret) {
            return $this->fail('2FA belum di-setup.', [], 422);
        }

        if (!$google2fa->verifyKey($user->two_factor_secret, $data['code'])) {
            activity()->log('2fa.verify.failed');
            return $this->fail('Kode 2FA tidak valid.', [], 422);
        }

        $user->two_factor_enabled = true;
        $user->save();
        activity()->performedOn($user)->log('2fa.verified');

        return $this->ok(null, '2FA aktif.');
    }

    public function twoFactorDisable(Request $request)
    {
        $user = $request->user();
        $user->two_factor_secret = null;
        $user->two_factor_enabled = false;
        $user->save();
        activity()->performedOn($user)->log('2fa.disabled');

        return $this->ok(null, '2FA dinonaktifkan.');
    }
}
