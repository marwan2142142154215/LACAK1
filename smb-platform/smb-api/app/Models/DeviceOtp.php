<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeviceOtp extends Model
{
    protected $fillable = ['device_id', 'otp_hash', 'attempts', 'expires_at', 'used_at', 'created_by'];

    protected $casts = ['expires_at' => 'datetime', 'used_at' => 'datetime'];

    public function device() { return $this->belongsTo(Device::class); }
}
