<?php

namespace App\Services;

use App\Exceptions\User\DuplicateEmailException;
use App\Exceptions\User\InvalidCredentialsException;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Support\Facades\Auth;

class AuthService
{
    public function __construct(protected UserRepositoryInterface $userRepository)
    {
    }

    public function register(array $data): array
    {
        // Check for duplicate email
        $duplicateEmail = !empty($this->userRepository->findWhere(['email' => $data['email']]));

        if ($duplicateEmail) {
            throw new DuplicateEmailException();
        }

        return $this->userRepository->create($data);
    }

    public function login(array $data): string
    {
        if (! Auth::attempt($data)) {
            throw new InvalidCredentialsException();
        }

        $user = Auth::user();

        return $user->createToken('api_' . $user->role)->plainTextToken;
    }
}
