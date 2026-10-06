<?php

use App\Models\User;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

test('valid login returns token', function () {
    $user = User::factory()->create(['password' => bcrypt('secret123')]);

    $response = postJson('/api/v1/auth/login', [
        'email' => $user->email,
        'password' => 'secret123',
        'device_name' => 'test',
    ]);

    $response->assertStatus(200)->assertJsonPath('success', true);
    expect($response->json('data.access_token'))->not->toBeEmpty();
});

test('invalid login rejected', function () {
    $user = User::factory()->create(['password' => bcrypt('secret123')]);

    postJson('/api/v1/auth/login', [
        'email' => $user->email,
        'password' => 'wrong',
        'device_name' => 'test',
    ])->assertStatus(401)->assertJsonPath('success', false);
});

test('devices endpoint requires auth', function () {
    getJson('/api/v1/devices')->assertStatus(401);
});
