<?php

namespace App\Services;

use App\Repositories\Contracts\UserRepositoryInterface;

class UserService
{
    public function __construct(protected UserRepositoryInterface $userRepository)
    {
    }

    public function create(array $data): array
    {
        $response = $this->userRepository->insert($data);
        return $response;
    }
}
