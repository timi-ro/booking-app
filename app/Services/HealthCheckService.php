<?php

namespace App\Services;

use App\Enums\HealthStatus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Throwable;

class HealthCheckService
{
    private const CACHE_TEST_KEY_PREFIX = 'health_check_';
    private const CACHE_TEST_VALUE = 'test';
    private const CACHE_TEST_TTL = 10;

    /**
     * Perform a deep health check on all critical services
     */
    public function checkAllServices(): array
    {
        $services = [
            'database' => $this->checkDatabase(),
            'cache' => $this->checkCache(),
        ];

        $allHealthy = collect($services)->every(
            fn($service) => $service['status'] === HealthStatus::HEALTHY->value
        );

        return [
            'status' => $allHealthy ? HealthStatus::HEALTHY->value : HealthStatus::UNHEALTHY->value,
            'timestamp' => now()->toIso8601String(),
            'services' => $services,
        ];
    }

    /**
     * Check database connectivity
     */
    protected function checkDatabase(): array
    {
        try {
            $startTime = microtime(true);
            DB::connection()->getPdo();
            DB::connection()->getDatabaseName();
            $responseTime = round((microtime(true) - $startTime) * 1000, 2);

            return [
                'status' => HealthStatus::HEALTHY->value,
                'response_time' => "{$responseTime}ms",
                'connection' => config('database.default'),
            ];
        } catch (Throwable $e) {
            return [
                'status' => HealthStatus::UNHEALTHY->value,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Check cache connectivity
     */
    protected function checkCache(): array
    {
        try {
            $startTime = microtime(true);
            $testKey = self::CACHE_TEST_KEY_PREFIX . time();

            Cache::put($testKey, self::CACHE_TEST_VALUE, self::CACHE_TEST_TTL);
            $retrieved = Cache::get($testKey);
            Cache::forget($testKey);

            $responseTime = round((microtime(true) - $startTime) * 1000, 2);

            if ($retrieved !== self::CACHE_TEST_VALUE) {
                return [
                    'status' => HealthStatus::UNHEALTHY->value,
                    'error' => 'Cache read/write mismatch',
                ];
            }

            return [
                'status' => HealthStatus::HEALTHY->value,
                'response_time' => "{$responseTime}ms",
                'driver' => config('cache.default'),
            ];
        } catch (Throwable $e) {
            return [
                'status' => HealthStatus::UNHEALTHY->value,
                'error' => $e->getMessage(),
            ];
        }
    }
}
