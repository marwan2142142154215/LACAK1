<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('devices', function (Blueprint $table) {
            $table->uuid('id')->primary(); // device_id immutable
            $table->foreignId('site_id')->constrained()->restrictOnDelete();
            $table->foreignId('team_id')->constrained()->restrictOnDelete();
            $table->string('name')->nullable();
            $table->string('serial_number')->nullable()->index();
            $table->string('android_version')->nullable();
            $table->integer('android_api')->nullable();
            $table->string('app_version')->nullable();
            $table->integer('app_version_code')->nullable();
            $table->string('status')->default('UNKNOWN')->index(); // ONLINE, DEGRADED, OFFLINE, UNKNOWN, LOCKED
            $table->string('connection_state')->default('disconnected');
            $table->boolean('managed_device')->default(false);
            $table->boolean('device_owner')->default(false);
            $table->jsonb('capabilities')->nullable();
            $table->timestamp('last_seen_at')->nullable()->index();
            $table->integer('battery_level')->nullable();
            $table->string('network_type')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('device_credentials', function (Blueprint $table) {
            $table->id();
            $table->uuid('device_id')->index();
            $table->string('credential_hash');
            $table->string('token')->unique();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();
            $table->foreign('device_id')->references('id')->on('devices')->cascadeOnDelete();
        });

        Schema::create('device_sessions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('device_id')->index();
            $table->string('socket_id')->nullable();
            $table->string('ip_address')->nullable();
            $table->string('connection_type')->default('websocket'); // websocket | https
            $table->timestamp('connected_at')->nullable();
            $table->timestamp('disconnected_at')->nullable();
            $table->timestamps();
            $table->foreign('device_id')->references('id')->on('devices')->cascadeOnDelete();
        });

        Schema::create('device_heartbeats', function (Blueprint $table) {
            $table->id();
            $table->uuid('device_id')->index();
            $table->timestamp('recorded_at')->index();
            $table->integer('battery_level')->nullable();
            $table->string('network_type')->nullable();
            $table->string('connection_state')->nullable();
            $table->string('app_version')->nullable();
            $table->integer('android_api')->nullable();
            $table->timestamp('last_location_at')->nullable();
            $table->jsonb('meta')->nullable();
            $table->timestamps();
            $table->foreign('device_id')->references('id')->on('devices')->cascadeOnDelete();
            $table->index(['device_id', 'recorded_at']);
        });

        Schema::create('device_locations', function (Blueprint $table) {
            $table->id();
            $table->uuid('device_id')->index();
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->decimal('accuracy', 8, 2)->nullable();
            $table->string('source')->default('gps');
            $table->integer('battery_level')->nullable();
            $table->string('network_type')->nullable();
            $table->timestamp('recorded_at')->index();
            $table->timestamps();
            $table->foreign('device_id')->references('id')->on('devices')->cascadeOnDelete();
            $table->index(['device_id', 'recorded_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('device_locations');
        Schema::dropIfExists('device_heartbeats');
        Schema::dropIfExists('device_sessions');
        Schema::dropIfExists('device_credentials');
        Schema::dropIfExists('devices');
    }
};
