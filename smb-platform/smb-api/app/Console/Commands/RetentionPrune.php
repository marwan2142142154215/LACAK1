<?php

namespace App\Console\Commands;

use App\Models\DeviceCommandLog;
use App\Models\DeviceHeartbeat;
use App\Models\DeviceLocation;
use App\Models\SystemSetting;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RetentionPrune extends Command
{
    protected $signature = 'smb:retention';

    protected $description = 'Prune heartbeat/location/command_log/activity_log records older than configured retention (default 30 days).';

    public function handle(): int
    {
        $setting = SystemSetting::where('key', 'retention')->first();
        $days = (int) ($setting?->value['days'] ?? 30);

        $cutoff = now()->subDays($days);

        $locations = DeviceLocation::where('recorded_at', '<', $cutoff)->delete();
        $heartbeats = DeviceHeartbeat::where('recorded_at', '<', $cutoff)->delete();
        $commandLogs = DeviceCommandLog::where('created_at', '<', $cutoff)->delete();

        DB::table('activity_log')->where('created_at', '<', $cutoff)->delete();

        $this->info("Pruned > {$days}d: locations={$locations}, heartbeats={$heartbeats}, command_logs={$commandLogs}");

        return Command::SUCCESS;
    }
}
