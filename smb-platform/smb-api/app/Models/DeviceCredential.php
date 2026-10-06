<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeviceCredential extends Model
{
    protected $fillable = ['device_id', 'credential_hash', 'token', 'expires_at', 'revoked_at'];

    protected $casts = ['expires_at' => 'datetime', 'revoked_at' => 'datetime'];

    protected $hidden = ['credential_hash'];

    public function device() { return $this->belongsTo(Device::class); }
}
