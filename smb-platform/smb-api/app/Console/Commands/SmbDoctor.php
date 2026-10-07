<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class SmbDoctor extends Command
{
    protected $signature = 'smb:doctor';

    protected $description = 'Check health of all SMB services.';

    public function handle(): int
    {
        $checks = [];

        try { DB::connection()->getPdo(); $checks['PostgreSQL'] = 'OK'; } catch (\Throwable) { $checks['PostgreSQL'] = 'FAIL'; }

        try { Cache::store('redis')->put('doc_ping', '1', 5); $checks['Redis'] = Cache::store('redis')->pull('doc_ping') === '1' ? 'OK' : 'FAIL'; } catch (\Throwable) { $checks['Redis'] = 'FAIL'; }

        $checks['Laravel'] = 'OK';

        try {
            $ch = curl_init('http://127.0.0.1:3333/');
            curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 5]);
            curl_exec($ch);
            $checks['AdonisJS'] = curl_errno($ch) === 0 ? 'OK' : 'FAIL';
        } catch (\Throwable) { $checks['AdonisJS'] = 'FAIL'; }

        try {
            $fp = @fsockopen('127.0.0.1', 8080, $errno, $errstr, 3);
            $checks['WebSocket'] = $fp ? 'OK' : 'FAIL ('.trim($errstr).')';
            if ($fp) fclose($fp);
        } catch (\Throwable) { $checks['WebSocket'] = 'FAIL'; }

        try {
            $output = shell_exec('tasklist /FI "IMAGENAME eq cloudflared.exe" 2>NUL');
            $checks['Cloudflare Tunnel'] = stripos($output ?? '', 'cloudflared.exe') !== false ? 'OK' : 'FAIL (process not running)';
        } catch (\Throwable) { $checks['Cloudflare Tunnel'] = 'UNKNOWN'; }

        $checks['Storage'] = is_writable(storage_path()) ? 'OK' : 'FAIL (storage not writable)';

        $checks['Telegram'] = env('TELEGRAM_BOT_TOKEN') ? 'OK' : 'SKIP (token not configured)';

        foreach ($checks as $k => $v) {
            $this->line(str_starts_with($v, 'OK') ? "[OK] {$k}" : "[FAIL/SKIP] {$k}: {$v}");
        }

        return in_array('FAIL', array_map(fn ($v) => str_starts_with($v, 'FAIL') ? 'FAIL' : 'OK', $checks)) ? Command::FAILURE : Command::SUCCESS;
    }
}
