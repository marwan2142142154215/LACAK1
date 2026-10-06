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
            'email' => 'required|email',
            'password' => 'required|string',
            'device_name' => 'required|string|max:128',
        ]);

        $user = User::where('email', $data['email'])->first();

        if (!$user || !Hash::check($data['password'], $user->password)) {
            activity()->withProperties(['email' => $data['email']])->log('auth.login.failed');
            return $this->fail('Kredensial tidak valid.', [], 401);
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
}
