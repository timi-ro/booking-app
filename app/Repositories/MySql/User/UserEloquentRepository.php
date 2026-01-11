<?php

namespace App\Repositories\MySql\User;

use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;

class UserEloquentRepository implements UserRepositoryInterface
{
    public function create(array $data): array
    {
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => bcrypt($data['password']),
            'role' => $data['role'],
        ]);

        $token = $user->createToken('api_'.$data['role'])->plainTextToken;
        $arrayUser = $user->toArray();
        $arrayUser['token'] = $token;

        return $arrayUser;
    }

    public function findWhere(array $where): array
    {
        $user = User::where($where)->first();

        return $user ? $user->toArray() : [];
    }

    public function update(array $data): void {}

    public function delete(int $id): void {}
}
