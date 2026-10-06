<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Device;
use App\Models\DeviceLocation;
use App\Support\ApiResponse;
use Illuminate\Http\Request;

class LocationController extends Controller
{
    use ApiResponse;

    public function index(Device $device)
    {
        $locations = $device->locations()->orderByDesc('recorded_at')->paginate(request()->integer('per_page', 50));

        return $this->ok([
            'items' => $locations->items(),
            'total' => $locations->total(),
            'current_page' => $locations->currentPage(),
            'last_page' => $locations->lastPage(),
            'per_page' => $locations->perPage(),
        ]);
    }

    public function latest(Device $device)
    {
        return $this->ok($device->locations()->latest('recorded_at')->first());
    }
}
