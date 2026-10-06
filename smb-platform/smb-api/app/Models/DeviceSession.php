<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeviceSession extends Model
{
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = ['id', 'device_id', 'socket_id', 'ip_address', 'connection_type', 'connected_at', 'disconnected_at'];

    protected $casts = ['connected_at' => 'datetime', 'disconnected_at' => 'datetime'];

    public function device() { return $this->belongsTo(Device::class); }
}
