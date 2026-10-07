<?php

namespace App\Console\Commands;

use App\Models\TelegramAccount;
use App\Models\Device;
use App\Models\DeviceCommand;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class TelegramPoll extends Command
{
    protected $signature = 'telegram:poll';

    protected $description = 'Poll Telegram getUpdates and proxy authorized commands to the command layer.';

    public function handle(): int
    {
        $token = env('TELEGRAM_BOT_TOKEN');
        if (!$token) {
            $this->error('TELEGRAM_BOT_TOKEN tidak di-set di .env');
            return Command::FAILURE;
        }

        $this->info('Polling Telegram... (Ctrl+C untuk berhenti)');

        $offset = 0;
        while (true) {
            try {
                $res = Http::timeout(35)->get("https://api.telegram.org/bot{$token}/getUpdates", ['offset' => $offset, 'timeout' => 30]);
                foreach ($res->json('result', []) as $update) {
                    $offset = $update['update_id'] + 1;
                    $message = $update['message'] ?? null;
                    if (!$message || !isset($message['text'])) {
                        continue;
                    }
                    $this->handleMessage($message, $token);
                }
            } catch (\Throwable $e) {
                $this->error($e->getMessage());
                sleep(5);
            }
        }
    }

    private function handleMessage(array $message, string $token): void
    {
        $telegramId = (string) ($message['from']['id'] ?? '');
        $text = trim($message['text']);
        $chatId = $message['chat']['id'];

        $account = TelegramAccount::where('telegram_id', $telegramId)->where('status', 'active')->first();
        if (!$account) {
            Http::post("https://api.telegram.org/bot{$token}/sendMessage", ['chat_id' => $chatId, 'text' => 'Telegram ID tidak terdaftar.']);
            return;
        }

        $user = $account->user;
        $parts = preg_split('/\s+/', $text);
        $command = strtolower($parts[0] ?? '');

        try {
            switch ($command) {
                case '/devices':
                    $devices = Device::orderByDesc('last_seen_at')->limit(20)->get(['id', 'name', 'status']);
                    $list = $devices->map(fn ($d) => "{$d->id} | {$d->name} | {$d->status}")->implode("\n");
                    Http::post("https://api.telegram.org/bot{$token}/sendMessage", ['chat_id' => $chatId, 'text' => $list ?: 'Tidak ada device.']);
                    break;

                case '/status':
                    $id = $parts[1] ?? null;
                    $d = $id ? Device::find($id) : null;
                    $text = $d ? "{$d->name} | {$d->status} | battery {$d->battery_level}% | {$d->network_type} | last {$d->last_seen_at}" : 'Device tidak ditemukan.';
                    Http::post("https://api.telegram.org/bot{$token}/sendMessage", ['chat_id' => $chatId, 'text' => $text]);
                    break;

                case '/lock':
                case '/unlock':
                    $id = $parts[1] ?? null;
                    if (!$id) {
                        Http::post("https://api.telegram.org/bot{$token}/sendMessage", ['chat_id' => $chatId, 'text' => 'Gunakan: /lock DEVICE_ID']);
                        break;
                    }
                    $d = Device::find($id);
                    if (!$d) {
                        Http::post("https://api.telegram.org/bot{$token}/sendMessage", ['chat_id' => $chatId, 'text' => 'Device tidak ditemukan.']);
                        break;
                    }
                    $c = DeviceCommand::create([
                        'id' => (string) Str::uuid(),
                        'device_id' => $d->id,
                        'command_type' => $command === '/lock' ? 'lock' : 'unlock',
                        'idempotency_key' => (string) Str::uuid(),
                        'created_by' => $user->id,
                        'status' => 'QUEUED',
                        'expires_at' => now()->addMinutes(30),
                    ]);
                    $c->transitionTo('QUEUED', 'via telegram', $user->email);
                    activity()->withProperties(['device_id' => $d->id, 'telegram_id' => $telegramId])->log('telegram.'.$command);
                    Http::post("https://api.telegram.org/bot{$token}/sendMessage", ['chat_id' => $chatId, 'text' => ucfirst($command)." dikirim ke {$d->name}"]);
                    break;

                default:
                    Http::post("https://api.telegram.org/bot{$token}/sendMessage", ['chat_id' => $chatId, 'text' => "Perintah tidak dikenal. Gunakan /devices /status DEVICE_ID /lock DEVICE_ID /unlock DEVICE_ID"]);
            }
        } catch (\Throwable $e) {
            Http::post("https://api.telegram.org/bot{$token}/sendMessage", ['chat_id' => $chatId, 'text' => 'Error: '.$e->getMessage()]);
        }
    }
}
