<?php

use App\Models\DeviceMedia;
use App\Models\DeviceRegistrationCode;
use App\Models\Site;
use App\Models\Team;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use function Pest\Laravel\postJson;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

function registeredDevice(): array
{
    $site = Site::create(['name' => 'HQ', 'code' => 'HQ'.uniqid()]);
    $team = Team::create(['site_id' => $site->id, 'name' => 'Ops', 'code' => 'OPS'.uniqid()]);

    DeviceRegistrationCode::create([
        'code_hash' => hash('sha256', 'ABCD1234'),
        'site_id' => $site->id,
        'team_id' => $team->id,
        'expires_at' => now()->addDay(),
    ]);

    $reg = postJson('/api/v1/devices/register', ['registration_code' => 'ABCD1234']);
    $reg->assertStatus(201);

    return [$reg->json('data.device_id'), $reg->json('data.token')];
}

test('device uploads media and it is stored honestly', function () {
    Storage::fake('local');
    [$deviceId, $token] = registeredDevice();

    $response = postJson(
        "/api/v1/devices/{$deviceId}/media",
        ['file' => UploadedFile::fake()->image('capture.jpg', 640, 480), 'camera_lens' => 'front'],
        ['Authorization' => "Bearer $token"],
    );

    $response->assertStatus(201)->assertJsonPath('success', true);
    expect(DeviceMedia::count())->toBe(1);

    $media = DeviceMedia::first();
    expect($media->camera_lens)->toBe('front');
    expect($media->storage_path)->toStartWith('local:');
});

test('non-image upload is rejected', function () {
    Storage::fake('local');
    [$deviceId, $token] = registeredDevice();

    postJson(
        "/api/v1/devices/{$deviceId}/media",
        ['file' => UploadedFile::fake()->create('payload.txt', 10, 'text/plain')],
        ['Authorization' => "Bearer $token"],
    )->assertStatus(422);
});
