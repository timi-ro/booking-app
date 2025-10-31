<?php

namespace App\Services;

use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;

class UserService
{
    public function __construct(protected UserRepositoryInterface $userRepository)
    {
    }

    public function create(array $data): User
    {
        $response = $this->userRepository->insert($data);
        return $response;
    }
}
