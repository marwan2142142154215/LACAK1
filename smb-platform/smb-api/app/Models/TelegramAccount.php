<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TelegramAccount extends Model
{
    use SoftDeletes;

    protected $fillable = ['user_id', 'telegram_id', 'username', 'status'];

    public function user() { return $this->belongsTo(User::class); }
}
