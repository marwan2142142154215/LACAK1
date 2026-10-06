<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeviceLocation extends Model
{
    protected $fillable = ['device_id', 'latitude', 'longitude', 'accuracy', 'source', 'battery_level', 'network_type', 'recorded_at'];

    protected $casts = ['recorded_at' => 'datetime', 'latitude' => 'float', 'longitude' => 'float', 'accuracy' => 'float'];

    public function device() { return $this->belongsTo(Device::class); }
}
