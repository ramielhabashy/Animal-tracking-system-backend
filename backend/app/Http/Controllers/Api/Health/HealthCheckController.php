<?php

namespace App\Http\Controllers\Api\Health;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Http\JsonResponse;

class HealthCheckController extends Controller
{
    public function index(): JsonResponse
    {
        $checks = [
            'app' => $this->checkApp(),
            'database' => $this->checkDatabase(),
            'redis' => $this->checkRedis(),
        ];

        $healthy = collect($checks)->every(fn($check) => $check['status'] === 'healthy');

        return response()->json([
            'status' => $healthy ? 'healthy' : 'degraded',
            'timestamp' => now()->toISOString(),
            'checks' => $checks,
        ], $healthy ? 200 : 503);
    }

    public function database(): JsonResponse
    {
        $result = $this->checkDatabase();

        return response()->json($result, $result['status'] === 'healthy' ? 200 : 503);
    }

    public function redis(): JsonResponse
    {
        $result = $this->checkRedis();

        return response()->json($result, $result['status'] === 'healthy' ? 200 : 503);
    }

    protected function checkApp(): array
    {
        return [
            'status' => 'healthy',
            'message' => 'Application is running',
        ];
    }

    protected function checkDatabase(): array
    {
        try {
            DB::connection()->getPdo();
            $time = DB::select('SELECT NOW() as time')[0]->time;

            return [
                'status' => 'healthy',
                'message' => 'Database connection successful',
                'time' => $time,
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'message' => 'Database connection failed: ' . $e->getMessage(),
            ];
        }
    }

    protected function checkRedis(): array
    {
        try {
            $redis = Redis::connection();
            $redis->ping();

            return [
                'status' => 'healthy',
                'message' => 'Redis connection successful',
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'message' => 'Redis connection failed: ' . $e->getMessage(),
            ];
        }
    }
}
