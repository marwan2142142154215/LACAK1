<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Team extends Model
{
    use SoftDeletes;

    protected $fillable = ['site_id', 'name', 'code', 'description', 'is_active'];

    public function site()
    {
        return $this->belongsTo(Site::class);
    }

    public function devices()
    {
        return $this->hasMany(Device::class);
    }
}
