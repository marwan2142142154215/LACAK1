<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DevicePolicy extends Model
{
    protected $fillable = ['device_id', 'policy_type', 'config', 'is_active'];

    protected $casts = ['config' => 'array', 'is_active' => 'boolean'];
}
