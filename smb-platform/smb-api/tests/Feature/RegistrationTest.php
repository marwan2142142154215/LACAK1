<?php

use App\Models\Device;
use App\Models\DeviceCredential;
use App\Models\DeviceRegistrationCode;
use App\Models\Site;
use App\Models\Team;
use function Pest\Laravel\postJson;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

test('registration code issue + device register flow', function () {
    $site = Site::create(['name' => 'HQ', 'code' => 'HQ']);
    $team = Team::create(['site_id' => $site->id, 'name' => 'Ops', 'code' => 'OPS']);

    $user = App\Models\User::factory()->create();
    $token = $user->createToken('test')->plainTextToken;

    $response = postJson('/api/v1/registration-codes', ['site_id' => $site->id, 'team_id' => $team->id], ['Authorization' => "Bearer $token"]);
    $response->assertStatus(201);
    $code = $response->json('data.registration_code');

    $reg = postJson('/api/v1/devices/register', ['registration_code' => $code]);
    $reg->assertStatus(201);
    $deviceToken = $reg->json('data.token');
    $deviceId = $reg->json('data.device_id');

    // token works for heartbeat
    postJson("/api/v1/devices/{$deviceId}/heartbeat", ['battery_level' => 80, 'network_type' => 'wifi'], ['Authorization' => "Bearer $deviceToken"])->assertStatus(200);

    // code cannot be reused
    postJson('/api/v1/devices/register', ['registration_code' => $code])->assertStatus(422);
});

test('invalid registration code rejected', function () {
    postJson('/api/v1/devices/register', ['registration_code' => 'NOPE1234'])->assertStatus(422);
});
