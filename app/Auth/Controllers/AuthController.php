<?php

namespace App\Auth\Controllers;

use App\Auth\Services\AuthService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\UserLoginRequest;
use App\Http\Requests\Auth\UserRegisterRequest;
use App\Shared\Helpers\ResponseHelper;
use Symfony\Component\HttpFoundation\Response;

/**
 * @group Authentication
 *
 * APIs for user authentication and registration.
 */
class AuthController extends Controller
{
    public function __construct(
        protected AuthService $authService
    ) {}

    /**
     * Register a new user
     *
     * Create a new user account. Only accessible by admins.
     *
     * @bodyParam name string required User's full name. Example: John Doe
     * @bodyParam email string required User's email address. Example: john@example.com
     * @bodyParam password string required User's password (minimum 8 characters). Example: password123
     * @bodyParam role string required User role (customer or agency). Example: customer
     *
     * @response 201 {
     *   "code": 201,
     *   "message": "success",
     *   "data": {
     *     "id": 1,
     *     "name": "John Doe",
     *     "email": "john@example.com",
     *     "role": "customer"
     *   }
     * }
     */
    public function register(UserRegisterRequest $request)
    {
        $validated = $request->validated();

        $user = $this->authService->register($validated);

        return ResponseHelper::generateResponse($user, Response::HTTP_CREATED);
    }

    /**
     * Login
     *
     * Authenticate a user and receive an access token.
     *
     * @unauthenticated
     *
     * @bodyParam email string required User's email address. Example: john@example.com
     * @bodyParam password string required User's password. Example: password123
     *
     * @response 200 {
     *   "code": 200,
     *   "message": "success",
     *   "data": {
     *     "token": "1|abcdef123456..."
     *   }
     * }
     */
    public function login(UserLoginRequest $request)
    {
        $request = $request->validated();

        $token = $this->authService->login($request);

        return ResponseHelper::generateResponse(['token' => $token]);
    }

    public function testCustomerArea()
    {
        dd('ok');
    }
}
