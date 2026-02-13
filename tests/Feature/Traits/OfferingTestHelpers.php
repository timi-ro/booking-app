<?php

namespace Tests\Feature\Traits;

use App\Offering\Models\Offering;
use App\Offering\Models\OfferingDay;
use App\Offering\Models\OfferingTimeSlot;
use App\Auth\Models\User;

trait OfferingTestHelpers
{
    // API Endpoints
    protected string $agencyOfferingsUrl = '/api/agency/offerings';

    protected string $customerOfferingsUrl = '/api/customer/offerings';

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
            ],
        ]);
    }

    protected function getValidOfferingData(array $overrides = []): array
    {
        return array_merge([
            'title' => 'Test Offering',
            'description' => 'This is a test offering description',
            'price' => 99.99,
            'address_info' => '123 Test Street, Test City',
        ], $overrides);
    }

    protected function createOfferingsWithPrices(User $user, array $prices): array
    {
        return collect($prices)->map(function ($price) use ($user) {
            return Offering::factory()
                ->forUser($user->id)
                ->create(['price' => $price]);
        })->all();
    }

    protected function createOfferingsWithTitles(User $user, array $titles): array
    {
        return collect($titles)->map(function ($title) use ($user) {
            return Offering::factory()
                ->forUser($user->id)
                ->create(['title' => $title]);
        })->all();
    }

    protected function assertAllOfferingsBelongToUser(array $offerings, User $user): void
    {
        foreach ($offerings as $offering) {
            $this->assertEquals($user->id, $offering['user_id']);
        }
    }

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
