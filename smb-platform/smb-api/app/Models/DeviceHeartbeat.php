<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeviceHeartbeat extends Model
{
    protected $fillable = ['device_id', 'recorded_at', 'battery_level', 'network_type', 'connection_state', 'app_version', 'android_api', 'last_location_at', 'meta'];

    protected $casts = ['recorded_at' => 'datetime', 'last_location_at' => 'datetime', 'meta' => 'array'];

    public function device() { return $this->belongsTo(Device::class); }
}
