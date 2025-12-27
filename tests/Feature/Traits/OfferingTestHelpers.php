<?php

namespace Tests\Feature\Traits;

use App\Constants\UserRoles;
use App\Models\Offering;
use App\Models\OfferingDay;
use App\Models\OfferingTimeSlot;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Laravel\Sanctum\Sanctum;

trait OfferingTestHelpers
{
    // API Endpoints
    protected string $agencyOfferingsUrl = '/api/agency/offerings';
    protected string $customerOfferingsUrl = '/api/customer/offerings';

    /**
     * Create an agency user.
     */
    protected function createAgencyUser(array $attributes = []): User
    {
        return User::factory()->create(array_merge([
            'role' => UserRoles::AGENCY,
        ], $attributes));
    }

    /**
     * Create a customer user.
     */
    protected function createCustomerUser(array $attributes = []): User
    {
        return User::factory()->create(array_merge([
            'role' => UserRoles::CUSTOMER,
        ], $attributes));
    }

    /**
     * Act as an agency user with Sanctum token.
     */
    protected function actingAsAgency(?User $user = null): User
    {
        $agency = $user ?? $this->createAgencyUser();
        Sanctum::actingAs($agency);
        return $agency;
    }

    /**
     * Act as a customer user with Sanctum token.
     */
    protected function actingAsCustomer(?User $user = null): User
    {
        $customer = $user ?? $this->createCustomerUser();
        Sanctum::actingAs($customer);
        return $customer;
    }

    /**
     * Create an offering with related days and time slots.
     */
    protected function createOfferingWithDaysAndSlots(User $agency, array $options = []): array
    {
        $offering = Offering::factory()
            ->forUser($agency->id)
            ->create();

        $daysCount = $options['days_count'] ?? 3;
        $slotsPerDay = $options['slots_per_day'] ?? 4;

        $days = OfferingDay::factory()
            ->count($daysCount)
            ->forOffering($offering->id)
            ->create();

        $slots = collect();
        foreach ($days as $day) {
            $daySlots = OfferingTimeSlot::factory()
                ->count($slotsPerDay)
                ->forOfferingDay($day->id)
                ->create();
            $slots = $slots->merge($daySlots);
        }

        return [
            'offering' => $offering->fresh(['offeringDays', 'timeSlots']),
            'days' => $days,
            'slots' => $slots,
        ];
    }

    /**
     * Assert standard response structure.
     */
    protected function assertStandardResponse($response, int $expectedStatus = 200): void
    {
        $response->assertStatus($expectedStatus);
        $response->assertJsonStructure([
            'errorMessage',
            'data',
        ]);
    }

    /**
     * Assert offering JSON structure.
     */
    protected function assertOfferingStructure($response): void
    {
        $response->assertJsonStructure([
            'data' => [
                'id',
                'user_id',
                'title',
                'description',
                'price',
                'address_info',
                'created_at',
                'updated_at',
            ]
        ]);
    }

    /**
     * Clear all offerings cache.
     */
    protected function clearOfferingsCache(): void
    {
        Cache::flush();
    }

    /**
     * Get valid offering data for creation.
     */
    protected function getValidOfferingData(array $overrides = []): array
    {
        return array_merge([
            'title' => 'Test Offering',
            'description' => 'This is a test offering description',
            'price' => 99.99,
            'address_info' => '123 Test Street, Test City',
        ], $overrides);
    }

    /**
     * Create multiple offerings for a user with specific prices.
     */
    protected function createOfferingsWithPrices(User $user, array $prices): array
    {
        return collect($prices)->map(function ($price) use ($user) {
            return Offering::factory()
                ->forUser($user->id)
                ->create(['price' => $price]);
        })->all();
    }

    /**
     * Create offerings for a user with specific titles.
     */
    protected function createOfferingsWithTitles(User $user, array $titles): array
    {
        return collect($titles)->map(function ($title) use ($user) {
            return Offering::factory()
                ->forUser($user->id)
                ->create(['title' => $title]);
        })->all();
    }

    /**
     * Assert offering belongs to user.
     */
    protected function assertOfferingBelongsToUser(Offering $offering, User $user): void
    {
        $this->assertEquals($user->id, $offering->user_id);
    }

    /**
     * Assert all offerings in response belong to user.
     */
    protected function assertAllOfferingsBelongToUser(array $offerings, User $user): void
    {
        foreach ($offerings as $offering) {
            $this->assertEquals($user->id, $offering['user_id']);
        }
    }

    /**
     * Assert pagination metadata.
     */
    protected function assertPaginationMetadata($response, int $currentPage, int $total, int $perPage): void
    {
        $data = $response->json('data');
        $this->assertEquals($currentPage, $data['current_page']);
        $this->assertEquals($total, $data['total']);
        $this->assertEquals($perPage, $data['per_page']);
    }

    /**
     * Build query string from parameters.
     */
    protected function buildQueryString(array $params): string
    {
        return '?' . http_build_query($params);
    }

    /**
     * Get agency offering URL with optional ID.
     */
    protected function agencyOfferingUrl(?int $id = null, array $query = []): string
    {
        $url = $this->agencyOfferingsUrl;
        if ($id) {
            $url .= "/{$id}";
        }
        if ($query) {
            $url .= $this->buildQueryString($query);
        }
        return $url;
    }

    /**
     * Get customer offering URL with optional ID.
     */
    protected function customerOfferingUrl(?int $id = null, array $query = []): string
    {
        $url = $this->customerOfferingsUrl;
        if ($id) {
            $url .= "/{$id}";
        }
        if ($query) {
            $url .= $this->buildQueryString($query);
        }
        return $url;
    }
}
