<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateUserRequest;
use App\Services\UserService;

class UserController extends Controller
{
    public function __construct(
        protected UserService $userService
    ) {
    }

    public function create(CreateUserRequest $request)
    {
        $validated = $request->validated();

        $data = $this->userService->create($validated);

        return response()->json($data);
    }
}
