<?php

namespace App\Http\Controllers;

use App\Enums\HealthStatus;
use App\Services\HealthCheckService;
use Illuminate\Support\Facades\Redis;
use Symfony\Component\HttpFoundation\Response;

/**
 * @group Health Check
 *
 * Endpoints for monitoring application health and status.
 */
class HealthController extends Controller
{
    private const PING_STATUS = 'ok';

    public function __construct(
        protected HealthCheckService $healthCheckService
    ) {
    }

    /**
     * Ping
     *
     * Shallow health check that confirms the application is running. Used by load balancers and liveness probes.
     *
     * @unauthenticated
     * @response 200 {
     *   "status": "ok",
     *   "timestamp": "2025-12-15T10:30:00Z"
     * }
     */
    public function ping()
    {
        return response()->json([
            'status' => self::PING_STATUS,
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Health check
     *
     * Deep health check that verifies all critical services (database, cache, etc.). Used by readiness probes and monitoring.
     *
     * @unauthenticated
     * @response 200 {
     *   "status": "healthy",
     *   "timestamp": "2025-12-15T10:30:00Z",
     *   "services": {
     *     "database": "healthy",
     *     "redis": "healthy"
     *   }
     * }
     */
    public function healthCheck()
    {
        $health = $this->healthCheckService->checkAllServices();

        $statusCode = $health['status'] === HealthStatus::HEALTHY->value
            ? Response::HTTP_OK
            : Response::HTTP_SERVICE_UNAVAILABLE;

        // TODO: add redis
//        dd(Redis::ping());

        return response()->json($health, $statusCode);
    }
}
