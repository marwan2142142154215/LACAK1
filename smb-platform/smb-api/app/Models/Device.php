<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Device extends Model
{
    use SoftDeletes;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id', 'site_id', 'team_id', 'name', 'serial_number', 'android_version',
        'android_api', 'app_version', 'app_version_code', 'status', 'connection_state',
        'managed_device', 'device_owner', 'capabilities', 'last_seen_at',
        'battery_level', 'network_type',
    ];

    protected $casts = [
        'capabilities' => 'array',
        'last_seen_at' => 'datetime',
        'managed_device' => 'boolean',
        'device_owner' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (Device $device) {
            if (empty($device->id)) {
                $device->id = (string) Str::uuid();
            }
        });
    }

    public function site() { return $this->belongsTo(Site::class); }
    public function team() { return $this->belongsTo(Team::class); }
    public function credentials() { return $this->hasMany(DeviceCredential::class); }
    public function sessions() { return $this->hasMany(DeviceSession::class); }
    public function heartbeats() { return $this->hasMany(DeviceHeartbeat::class); }
    public function locations() { return $this->hasMany(DeviceLocation::class); }
    public function commands() { return $this->hasMany(DeviceCommand::class); }
    public function otps() { return $this->hasMany(DeviceOtp::class); }
    public function media() { return $this->hasMany(DeviceMedia::class); }
    public function policies() { return $this->hasMany(DevicePolicy::class); }
}
