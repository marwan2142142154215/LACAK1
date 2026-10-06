<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeviceMedia extends Model
{
    protected $fillable = ['device_id', 'command_id', 'media_type', 'storage_path', 'mime_type', 'size', 'hash', 'camera_lens'];
}
