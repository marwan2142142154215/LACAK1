<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeviceRegistrationCode extends Model
{
    protected $fillable = ['code_hash', 'site_id', 'team_id', 'created_by', 'expires_at', 'used_at', 'used_by_device_id'];

    protected $casts = ['expires_at' => 'datetime', 'used_at' => 'datetime'];

    public function site() { return $this->belongsTo(Site::class); }
    public function team() { return $this->belongsTo(Team::class); }
}
