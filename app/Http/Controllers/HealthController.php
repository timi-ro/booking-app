<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\UserLoginRequest;
use App\Http\Requests\Auth\UserRegisterRequest;
use App\Services\AuthService;
use Symfony\Component\HttpFoundation\Response;

class HealthController extends Controller
{
    public function __construct(
    ) {
    }

    public function healthCheck()
    {
        //TODO: parallel run
        // check db (redis & mysql)
        // check storage (ping & driver, [upload , download, delete {speed}])
        // sys info (cpu, memory, ping with detail )
        // email driver check (sen & driver info)
        // cache driver detail
    }
}
