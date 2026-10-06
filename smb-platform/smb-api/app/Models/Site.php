<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Site extends Model
{
    use SoftDeletes;

    protected $fillable = ['name', 'code', 'address', 'city', 'province', 'is_active'];

    public function teams()
    {
        return $this->hasMany(Team::class);
    }

    public function devices()
    {
        return $this->hasMany(Device::class);
    }
}
