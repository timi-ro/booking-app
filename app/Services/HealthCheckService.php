<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
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
            'redis' => $this->checkRedis(),
            'cache' => $this->checkCache(),
        ];

        $allHealthy = collect($services)->every(
            fn ($service) => $service['healthy'] === true
        );

        return [
            'healthy' => $allHealthy,
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
                'healthy' => true,
                'response_time' => "{$responseTime}ms",
                'connection' => config('database.default'),
            ];
        } catch (Throwable $e) {
            Log::channel('sentry')->error('Database health check failed', [
                'error' => $e->getMessage(),
                'connection' => config('database.default'),
            ]);

            return [
                'healthy' => false,
                'message' => 'Database service is unavailable',
            ];
        }
    }

    /**
     * Check Redis connectivity
     */
    protected function checkRedis(): array
    {
        try {
            $startTime = microtime(true);

            // Test default connection
            $pong = Redis::ping();

            // Test bookings connection
            $bookingsPong = Redis::connection('bookings')->ping();

            $responseTime = round((microtime(true) - $startTime) * 1000, 2);

            // Convert to string for comparison (predis returns Status object)
            $pongStr = (string) $pong;
            $bookingsPongStr = (string) $bookingsPong;

            if ($pongStr !== 'PONG' || $bookingsPongStr !== 'PONG') {
                Log::channel('sentry')->error('Redis health check failed', [
                    'default_response' => $pongStr,
                    'bookings_response' => $bookingsPongStr,
                    'connections' => ['default', 'bookings'],
                ]);

                return [
                    'healthy' => false,
                    'message' => 'Redis service is unavailable',
                ];
            }

            return [
                'healthy' => true,
                'response_time' => "{$responseTime}ms",
                'connections' => ['default', 'bookings'],
            ];
        } catch (Throwable $e) {
            Log::channel('sentry')->error('Redis health check failed', [
                'error' => $e->getMessage(),
                'connections' => ['default', 'bookings'],
            ]);

            return [
                'healthy' => false,
                'message' => 'Redis service is unavailable',
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
            $testKey = self::CACHE_TEST_KEY_PREFIX.time();

            Cache::put($testKey, self::CACHE_TEST_VALUE, self::CACHE_TEST_TTL);
            $retrieved = Cache::get($testKey);
            Cache::forget($testKey);

            $responseTime = round((microtime(true) - $startTime) * 1000, 2);

            if ($retrieved !== self::CACHE_TEST_VALUE) {
                Log::channel('sentry')->error('Cache health check failed', [
                    'reason' => 'Cache read/write mismatch',
                    'driver' => config('cache.default'),
                    'expected' => self::CACHE_TEST_VALUE,
                    'retrieved' => $retrieved,
                ]);

                return [
                    'healthy' => false,
                    'message' => 'Cache service is unavailable',
                ];
            }

            return [
                'healthy' => true,
                'response_time' => "{$responseTime}ms",
                'driver' => config('cache.default'),
            ];
        } catch (Throwable $e) {
            Log::channel('sentry')->error('Cache health check failed', [
                'error' => $e->getMessage(),
                'driver' => config('cache.default'),
            ]);

            return [
                'healthy' => false,
                'message' => 'Cache service is unavailable',
            ];
        }
    }
}
