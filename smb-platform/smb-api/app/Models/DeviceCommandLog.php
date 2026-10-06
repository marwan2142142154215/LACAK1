<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeviceCommandLog extends Model
{
    protected $fillable = ['command_id', 'device_id', 'from_status', 'to_status', 'actor', 'note'];
}
