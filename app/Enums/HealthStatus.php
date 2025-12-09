<?php

namespace App\Enums;

enum HealthStatus: string
{
    case HEALTHY = 'healthy';
    case UNHEALTHY = 'unhealthy';
    case DEGRADED = 'degraded';
}
