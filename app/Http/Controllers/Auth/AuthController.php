<?php

namespace App\Http\Controllers\Auth;

use App\Exceptions\User\AuthenticationException;
use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\UserLoginRequest;
use App\Http\Requests\Auth\UserRegisterRequest;
use App\Services\AuthService;
use Symfony\Component\HttpFoundation\Response;

class AuthController extends Controller
{
    public function __construct(
        protected AuthService $authService
    ) {
    }

    public function register(UserRegisterRequest $request)
    {
        $validated = $request->validated();

        $user = $this->authService->register($validated);

        return ResponseHelper::generateResponse($user, Response::HTTP_CREATED);
    }

    public function login(UserLoginRequest $request)
    {
        $request = $request->validated();

        $token = $this->authService->login($request);

        return ResponseHelper::generateResponse(['token' => $token]);
    }

    public function testCustomerArea()
    {
        dd("ok");
    }
}
