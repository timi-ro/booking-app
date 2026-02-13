<?php

namespace Tests\Feature\Traits;

use App\Auth\Models\User;

trait ResponseHelpers
{
    protected function assertStandardResponse($response, int $expectedStatus = 200): void
    {
        $response->assertStatus($expectedStatus);
        $response->assertJsonStructure([
            'errorMessage',
            'data',
        ]);
    }

    protected function assertPaginationMetadata($response, int $currentPage, int $total, int $perPage): void
    {
        $data = $response->json('data');
        $this->assertEquals($currentPage, $data['current_page']);
        $this->assertEquals($total, $data['total']);
        $this->assertEquals($perPage, $data['per_page']);
    }

    protected function assertAllItemsBelongToUser(array $items, User $user, string $foreignKey = 'user_id'): void
    {
        foreach ($items as $item) {
            $this->assertEquals($user->id, $item[$foreignKey]);
        }
    }

    protected function buildQueryString(array $params): string
    {
        return '?'.http_build_query($params);
    }
}
