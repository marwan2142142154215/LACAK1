<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Support\ApiResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class HealthController extends Controller
{
    use ApiResponse;

    public function index()
    {
        $checks = [];

        try {
            DB::connection()->getPdo();
            $checks['postgresql'] = 'ok';
        } catch (\Throwable) {
            $checks['postgresql'] = 'fail';
        }

        try {
            Cache::store('redis')->put('health_ping', '1', 5);
            $checks['redis'] = Cache::store('redis')->pull('health_ping') === '1' ? 'ok' : 'fail';
        } catch (\Throwable) {
            $checks['redis'] = 'fail';
        }

        $status = in_array('fail', $checks, true) ? 503 : 200;

        return $this->ok(['checks' => $checks], 'Health check.', $status);
    }
}
