<?php

namespace Tests\Feature\Traits;

use App\Auth\Constants\UserRoles;
use App\Auth\Models\User;
use Laravel\Sanctum\Sanctum;

trait AuthenticationHelpers
{
    protected function createAgencyUser(array $attributes = []): User
    {
        return User::factory()->create(array_merge([
            'role' => UserRoles::AGENCY,
        ], $attributes));
    }

    protected function createCustomerUser(array $attributes = []): User
    {
        return User::factory()->create(array_merge([
            'role' => UserRoles::CUSTOMER,
        ], $attributes));
    }

    protected function actingAsAgency(?User $user = null): User
    {
        $agency = $user ?? $this->createAgencyUser();
        Sanctum::actingAs($agency);

        return $agency;
    }

    protected function actingAsCustomer(?User $user = null): User
    {
        $customer = $user ?? $this->createCustomerUser();
        Sanctum::actingAs($customer);

        return $customer;
    }

    protected function actingAsUnauthenticated(): void
    {
        $this->app['auth']->forgetGuards();
    }
}
