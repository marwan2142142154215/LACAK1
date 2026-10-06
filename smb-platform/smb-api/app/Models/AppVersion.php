<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppVersion extends Model
{
    protected $fillable = ['app_name', 'version', 'version_code', 'minimum_supported_api', 'download_path', 'checksum', 'released_at'];

    protected $casts = ['released_at' => 'datetime'];
}
