<?php

namespace App\Http\Controllers;

use App\Enums\HealthStatus;
use App\Services\HealthCheckService;
use Illuminate\Support\Facades\Redis;
use Symfony\Component\HttpFoundation\Response;

class HealthController extends Controller
{
    private const PING_STATUS = 'ok';

    public function __construct(
        protected HealthCheckService $healthCheckService
    ) {
    }

    /**
     * Shallow health check - just confirms app is running
     * Used by load balancers and liveness probes
     */
    public function ping()
    {
        return response()->json([
            'status' => self::PING_STATUS,
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Deep health check - checks all critical services
     * Used by readiness probes and monitoring
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
