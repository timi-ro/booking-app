<?php

namespace App\Services;

use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Support\Facades\Auth;

class AuthService
{
    public function __construct(protected UserRepositoryInterface $userRepository)
    {
    }

    public function register(array $data): array
    {
        //TODO: add duplicate account check
        return $this->userRepository->create($data);
    }

    public function login(array $data): string
    {
        if (! Auth::attempt($data)) {
            //TODO: throw and handle an exception
            dd("Wrong username or password");
        }

        $user = Auth::user();

        return $user->createToken('api_' . $user->role)->plainTextToken;
    }
}
