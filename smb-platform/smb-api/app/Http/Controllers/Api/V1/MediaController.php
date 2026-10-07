<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Device;
use App\Models\DeviceMedia;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MediaController extends Controller
{
    use ApiResponse;

    /**
     * Device uploads captured media (photo). Stores to DigitalOcean Spaces when
     * configured, otherwise falls back to the private local disk. The controller
     * never fabricates a public URL for a private object.
     */
    public function store(Request $request, Device $device)
    {
        $data = $request->validate([
            'file' => 'required|file|mimes:jpg,jpeg,png,webp|max:10240',
            'media_type' => 'nullable|in:photo,video',
            'command_id' => 'nullable|uuid',
            'camera_lens' => 'nullable|in:front,back',
        ]);

        $file = $request->file('file');
        $disk = $this->mediaDisk();
        $path = 'device-media/'.$device->id.'/'.date('Y/m/d').'/'.Str::random(16).'.'.$file->getClientOriginalExtension();

        Storage::disk($disk)->put($path, file_get_contents($file->getRealPath()));

        $media = DeviceMedia::create([
            'device_id' => $device->id,
            'command_id' => $data['command_id'] ?? null,
            'media_type' => $data['media_type'] ?? 'photo',
            'storage_path' => $disk.':'.$path,
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'hash' => hash_file('sha256', $file->getRealPath()),
            'camera_lens' => $data['camera_lens'] ?? null,
        ]);

        activity()->withProperties(['device_id' => $device->id, 'media_id' => $media->id, 'disk' => $disk])->log('media.uploaded');

        return $this->ok($this->present($media), 'Media diterima.', 201);
    }

    public function index(Request $request, Device $device)
    {
        $perPage = min((int) $request->input('per_page', 15), 100);
        $page = $device->media()->latest()->paginate($perPage);

        return $this->ok([
            'items' => collect($page->items())->map(fn (DeviceMedia $m) => $this->present($m))->values(),
            'total' => $page->total(),
            'current_page' => $page->currentPage(),
            'last_page' => $page->lastPage(),
            'per_page' => $page->perPage(),
        ], 'Daftar media.');
    }

    public function show(DeviceMedia $media)
    {
        return $this->ok($this->present($media), 'Detail media.');
    }

    /**
     * Stream the object back through the API. Works for both the private local
     * disk and DO Spaces (which is private by default), so no public bucket is
     * required.
     */
    public function download(DeviceMedia $media)
    {
        [$disk, $path] = array_pad(explode(':', $media->storage_path, 2), 2, null);

        if (!$disk || !$path || !Storage::disk($disk)->exists($path)) {
            return $this->fail('File media tidak ditemukan.', [], 404);
        }

        return Storage::disk($disk)->download($path, basename($path), ['Content-Type' => $media->mime_type]);
    }

    private function present(DeviceMedia $media): array
    {
        return [
            'id' => $media->id,
            'device_id' => $media->device_id,
            'command_id' => $media->command_id,
            'media_type' => $media->media_type,
            'mime_type' => $media->mime_type,
            'size' => $media->size,
            'hash' => $media->hash,
            'camera_lens' => $media->camera_lens,
            'url' => route('api.v1.media.download', ['media' => $media->id]),
            'created_at' => $media->created_at,
        ];
    }

    /**
     * Prefer Spaces only when it is actually configured; otherwise use local.
     * This avoids runtime crashes from an unconfigured bucket.
     */
    private function mediaDisk(): string
    {
        $spaces = config('filesystems.disks.spaces');

        return (!empty($spaces['key']) && !empty($spaces['bucket'])) ? 'spaces' : 'local';
    }
}
