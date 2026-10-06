<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeviceCommand extends Model
{
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = ['id', 'device_id', 'command_type', 'idempotency_key', 'created_by', 'status', 'payload', 'result', 'error_message', 'expires_at', 'sent_at', 'delivered_at', 'received_at', 'executed_at', 'completed_at'];

    protected $casts = [
        'payload' => 'array',
        'expires_at' => 'datetime', 'sent_at' => 'datetime', 'delivered_at' => 'datetime',
        'received_at' => 'datetime', 'executed_at' => 'datetime', 'completed_at' => 'datetime',
    ];

    public function device() { return $this->belongsTo(Device::class); }
    public function logs() { return $this->hasMany(DeviceCommandLog::class, 'command_id'); }

    public function transitionTo(string $status, ?string $note = null, ?string $actor = null): void
    {
        $from = $this->status;
        $this->update(['status' => $status]);
        DeviceCommandLog::create([
            'command_id' => $this->id,
            'device_id' => $this->device_id,
            'from_status' => $from,
            'to_status' => $status,
            'actor' => $actor,
            'note' => $note,
        ]);
    }
}
