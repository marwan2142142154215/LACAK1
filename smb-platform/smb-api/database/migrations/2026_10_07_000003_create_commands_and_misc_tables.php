<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('device_commands', function (Blueprint $table) {
            $table->uuid('id')->primary(); // command_id
            $table->uuid('device_id')->index();
            $table->string('command_type')->index();
            $table->string('idempotency_key')->unique();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status')->default('PENDING')->index();
            $table->jsonb('payload')->nullable();
            $table->text('result')->nullable();
            $table->string('error_message')->nullable();
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->timestamp('executed_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->foreign('device_id')->references('id')->on('devices')->cascadeOnDelete();
            $table->index(['device_id', 'status']);
        });

        Schema::create('device_command_logs', function (Blueprint $table) {
            $table->id();
            $table->uuid('command_id')->index();
            $table->uuid('device_id')->index();
            $table->string('from_status')->nullable();
            $table->string('to_status');
            $table->string('actor')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();
            $table->index(['command_id', 'created_at']);
        });

        Schema::create('device_registration_codes', function (Blueprint $table) {
            $table->id();
            $table->string('code_hash')->unique();
            $table->foreignId('site_id')->constrained()->restrictOnDelete();
            $table->foreignId('team_id')->constrained()->restrictOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('expires_at');
            $table->timestamp('used_at')->nullable();
            $table->string('used_by_device_id')->nullable();
            $table->timestamps();
            $table->index(['site_id', 'team_id']);
        });

        Schema::create('device_otps', function (Blueprint $table) {
            $table->id();
            $table->uuid('device_id')->index();
            $table->string('otp_hash');
            $table->integer('attempts')->default(0);
            $table->timestamp('expires_at');
            $table->timestamp('used_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->foreign('device_id')->references('id')->on('devices')->cascadeOnDelete();
        });

        Schema::create('device_media', function (Blueprint $table) {
            $table->id();
            $table->uuid('device_id')->index();
            $table->uuid('command_id')->nullable()->index();
            $table->string('media_type')->default('photo');
            $table->string('storage_path');
            $table->string('mime_type');
            $table->unsignedBigInteger('size')->nullable();
            $table->string('hash', 64)->nullable();
            $table->string('camera_lens')->nullable();
            $table->timestamps();
            $table->foreign('device_id')->references('id')->on('devices')->cascadeOnDelete();
        });

        Schema::create('device_policies', function (Blueprint $table) {
            $table->id();
            $table->uuid('device_id')->index();
            $table->string('policy_type');
            $table->jsonb('config')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->foreign('device_id')->references('id')->on('devices')->cascadeOnDelete();
            $table->unique(['device_id', 'policy_type']);
        });

        Schema::create('telegram_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('telegram_id')->unique();
            $table->string('username')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->uuid('device_id')->nullable()->index();
            $table->string('type');
            $table->string('title');
            $table->text('body')->nullable();
            $table->boolean('is_read')->default(false);
            $table->timestamps();
        });

        Schema::create('system_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->jsonb('value')->nullable();
            $table->timestamps();
        });

        Schema::create('app_versions', function (Blueprint $table) {
            $table->id();
            $table->string('app_name'); // smb-tracker | smb-master | smb-server
            $table->string('version');
            $table->integer('version_code');
            $table->integer('minimum_supported_api')->default(26);
            $table->string('download_path')->nullable();
            $table->string('checksum')->nullable();
            $table->timestamp('released_at')->nullable();
            $table->timestamps();
            $table->unique(['app_name', 'version_code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app_versions');
        Schema::dropIfExists('system_settings');
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('telegram_accounts');
        Schema::dropIfExists('device_policies');
        Schema::dropIfExists('device_media');
        Schema::dropIfExists('device_otps');
        Schema::dropIfExists('device_registration_codes');
        Schema::dropIfExists('device_command_logs');
        Schema::dropIfExists('device_commands');
    }
};
