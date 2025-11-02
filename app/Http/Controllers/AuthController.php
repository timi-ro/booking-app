<?php

namespace App\Http\Controllers;

use App\Http\Requests\Auth\UserLoginRequest;
use App\Http\Requests\Auth\UserRegisterRequest;
use App\Services\AuthService;

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

        //TODO: add response template
        return response()->json($user);
    }

    public function login(UserLoginRequest $request)
    {
        $request = $request->validated();

        $token = $this->authService->login($request);

        return response()->json(['token' => $token]);
    }

    public function testCustomerArea()
    {
        dd("ok");
    }
}
