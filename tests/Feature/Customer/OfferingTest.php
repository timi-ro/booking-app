<?php

namespace Tests\Feature\Customer;

use App\Models\Offering;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\Feature\Traits\OfferingTestHelpers;
use Tests\TestCase;

class OfferingTest extends TestCase
{
    use RefreshDatabase, OfferingTestHelpers;

    protected User $customer;
    protected User $agency;

    protected function setUp(): void
    {
        parent::setUp();

        $this->customer = $this->actingAsCustomer();
        $this->agency = $this->createAgencyUser();
    }

    // ===== BROWSE Offerings Tests =====

    public function test_customer_can_browse_all_offerings(): void
    {
        Offering::factory()->count(5)->forUser($this->agency->id)->create();

        $response = $this->getJson($this->customerOfferingsUrl);

        $this->assertStandardResponse($response);
        $this->assertCount(5, $response->json('data.data'));
    }

    public function test_browse_offerings_is_paginated(): void
    {
        Offering::factory()->count(25)->forUser($this->agency->id)->create();

        $response = $this->getJson($this->customerOfferingUrl(null, ['page' => 1, 'page_size' => 10]));

        $this->assertStandardResponse($response);
        $this->assertEquals(10, count($response->json('data.data')));
        $this->assertPaginationMetadata($response, 1, 25, 10);
    }

    public function test_customer_can_search_offerings_by_title(): void
    {
        $this->createOfferingsWithTitles($this->agency, [
            'Desert Safari Adventure',
            'Mountain Hiking',
            'Beach Relaxation'
        ]);

        $response = $this->getJson($this->customerOfferingUrl(null, ['search' => 'desert']));

        $this->assertStandardResponse($response);
        $data = $response->json('data.data');
        $this->assertCount(1, $data);
        $this->assertStringContainsStringIgnoringCase('desert', $data[0]['title']);
    }

    public function test_customer_can_search_offerings_by_description(): void
    {
        Offering::factory()->forUser($this->agency->id)->create([
            'title' => 'Activity One',
            'description' => 'Experience the thrill of desert dunes'
        ]);
        Offering::factory()->forUser($this->agency->id)->create([
            'title' => 'Activity Two',
            'description' => 'Enjoy mountain views'
        ]);

        $response = $this->getJson($this->customerOfferingUrl(null, ['search' => 'thrill']));

        $this->assertStandardResponse($response);
        $data = $response->json('data.data');
        $this->assertCount(1, $data);
        $this->assertStringContainsStringIgnoringCase('thrill', $data[0]['description']);
    }

    public function test_search_is_case_insensitive(): void
    {
        Offering::factory()->forUser($this->agency->id)->create(['title' => 'DESERT Safari']);

        $response = $this->getJson($this->customerOfferingUrl(null, ['search' => 'desert']));

        $this->assertStandardResponse($response);
        $this->assertCount(1, $response->json('data.data'));
    }

    public function test_customer_can_filter_by_min_price(): void
    {
        $this->createOfferingsWithPrices($this->agency, [50, 150, 250]);

        $response = $this->getJson($this->customerOfferingUrl(null, ['min_price' => 100]));

        $this->assertStandardResponse($response);
        $data = $response->json('data.data');
        $this->assertCount(2, $data);

        foreach ($data as $offering) {
            $this->assertGreaterThanOrEqual(100, $offering['price']);
        }
    }

    public function test_customer_can_filter_by_max_price(): void
    {
        $this->createOfferingsWithPrices($this->agency, [50, 150, 250]);

        $response = $this->getJson($this->customerOfferingUrl(null, ['min_price' => 0, 'max_price' => 200]));

        $this->assertStandardResponse($response);
        $data = $response->json('data.data');
        $this->assertCount(2, $data);

        foreach ($data as $offering) {
            $this->assertLessThanOrEqual(200, $offering['price']);
        }
    }

    public function test_customer_can_filter_by_price_range(): void
    {
        $this->createOfferingsWithPrices($this->agency, [50, 150, 250, 350]);

        $response = $this->getJson($this->customerOfferingUrl(null, [
            'min_price' => 100,
            'max_price' => 300
        ]));

        $this->assertStandardResponse($response);
        $data = $response->json('data.data');
        $this->assertCount(2, $data);

        foreach ($data as $offering) {
            $this->assertGreaterThanOrEqual(100, $offering['price']);
            $this->assertLessThanOrEqual(300, $offering['price']);
        }
    }

    public function test_customer_can_sort_by_price_ascending(): void
    {
        $this->createOfferingsWithPrices($this->agency, [250, 50, 150]);

        $response = $this->getJson($this->customerOfferingUrl(null, [
            'sort_by' => 'price',
            'sort_direction' => 'asc'
        ]));

        $this->assertStandardResponse($response);
        $data = $response->json('data.data');
        $this->assertEquals(50, $data[0]['price']);
        $this->assertEquals(150, $data[1]['price']);
        $this->assertEquals(250, $data[2]['price']);
    }

    public function test_customer_can_sort_by_price_descending(): void
    {
        $this->createOfferingsWithPrices($this->agency, [50, 250, 150]);

        $response = $this->getJson($this->customerOfferingUrl(null, [
            'sort_by' => 'price',
            'sort_direction' => 'desc'
        ]));

        $this->assertStandardResponse($response);
        $data = $response->json('data.data');
        $this->assertEquals(250, $data[0]['price']);
        $this->assertEquals(150, $data[1]['price']);
        $this->assertEquals(50, $data[2]['price']);
    }

    public function test_customer_can_sort_by_title(): void
    {
        $this->createOfferingsWithTitles($this->agency, ['Zebra Safari', 'Adventure Park', 'Mountain Trek']);

        $response = $this->getJson($this->customerOfferingUrl(null, [
            'sort_by' => 'title',
            'sort_direction' => 'asc'
        ]));

        $this->assertStandardResponse($response);
        $data = $response->json('data.data');
        $this->assertEquals('Adventure Park', $data[0]['title']);
        $this->assertEquals('Mountain Trek', $data[1]['title']);
        $this->assertEquals('Zebra Safari', $data[2]['title']);
    }

    public function test_customer_can_combine_multiple_filters(): void
    {
        Offering::factory()->forUser($this->agency->id)->create(['title' => 'Desert Safari', 'price' => 150]);
        Offering::factory()->forUser($this->agency->id)->create(['title' => 'Desert Adventure', 'price' => 250]);
        Offering::factory()->forUser($this->agency->id)->create(['title' => 'Beach Resort', 'price' => 120]);

        $response = $this->getJson($this->customerOfferingUrl(null, [
            'search' => 'desert',
            'min_price' => 100,
            'max_price' => 200,
            'sort_by' => 'price',
            'sort_direction' => 'asc'
        ]));

        $this->assertStandardResponse($response);
        $data = $response->json('data.data');
        $this->assertCount(1, $data);
        $this->assertStringContainsStringIgnoringCase('desert', $data[0]['title']);
        $this->assertEquals(150, $data[0]['price']);
    }

    public function test_browse_only_shows_non_deleted_offerings(): void
    {
        $offering1 = Offering::factory()->forUser($this->agency->id)->create(['title' => 'Active']);
        $offering2 = Offering::factory()->forUser($this->agency->id)->create(['title' => 'Deleted']);
        $offering2->delete();

        $response = $this->getJson($this->customerOfferingsUrl);

        $this->assertStandardResponse($response);
        $data = $response->json('data.data');
        $this->assertCount(1, $data);
        $this->assertEquals('Active', $data[0]['title']);
    }

    public function test_empty_offerings_list_returns_successfully(): void
    {
        $response = $this->getJson($this->customerOfferingsUrl);

        $this->assertStandardResponse($response);
        $this->assertEmpty($response->json('data.data'));
    }

    // ===== VIEW Offering Details Tests =====

    public function test_customer_can_view_offering_details(): void
    {
        $data = $this->createOfferingWithDaysAndSlots($this->agency);
        $offering = $data['offering'];

        $response = $this->getJson($this->customerOfferingUrl($offering->id));

        $this->assertStandardResponse($response);
        $responseData = $response->json('data');
        $this->assertEquals($offering->id, $responseData['id']);
        $this->assertEquals($offering->title, $responseData['title']);
    }

    public function test_offering_details_includes_offering_days(): void
    {
        $data = $this->createOfferingWithDaysAndSlots($this->agency, ['days_count' => 3]);
        $offering = $data['offering'];

        $response = $this->getJson($this->customerOfferingUrl($offering->id));

        $this->assertStandardResponse($response);
        $responseData = $response->json('data');
        $this->assertArrayHasKey('offering_days', $responseData);
        $this->assertCount(3, $responseData['offering_days']);
    }

    public function test_offering_details_includes_time_slots(): void
    {
        $data = $this->createOfferingWithDaysAndSlots($this->agency, [
            'days_count' => 2,
            'slots_per_day' => 3
        ]);
        $offering = $data['offering'];

        $response = $this->getJson($this->customerOfferingUrl($offering->id));

        $this->assertStandardResponse($response);
        $responseData = $response->json('data');

        foreach ($responseData['offering_days'] as $day) {
            $this->assertArrayHasKey('time_slots', $day);
            $this->assertCount(3, $day['time_slots']);
        }
    }

    public function test_viewing_nonexistent_offering_returns_404(): void
    {
        $this->getJson($this->customerOfferingUrl(999999))
            ->assertStatus(404);
    }

    public function test_viewing_soft_deleted_offering_returns_404(): void
    {
        $offering = Offering::factory()->forUser($this->agency->id)->create();
        $offering->delete();

        $this->getJson($this->customerOfferingUrl($offering->id))
            ->assertStatus(404);
    }

    // ===== Authorization Tests =====

    public function test_unauthenticated_user_cannot_browse_offerings(): void
    {
        $this->app['auth']->forgetGuards();

        $this->getJson($this->customerOfferingsUrl)
            ->assertStatus(401);
    }

    public function test_unauthenticated_user_cannot_view_offering_details(): void
    {
        $this->app['auth']->forgetGuards();

        $this->getJson($this->customerOfferingUrl(1))
            ->assertStatus(401);
    }

    public function test_agency_cannot_access_customer_endpoints(): void
    {
        $this->actingAsAgency();

        $this->getJson($this->customerOfferingsUrl)
            ->assertStatus(401);
    }

    // ===== Validation Tests =====

    public function test_browse_validates_page_is_integer(): void
    {
        $this->getJson($this->customerOfferingUrl(null, ['page' => 'abc']))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['page']);
    }

    public function test_browse_validates_min_price_is_numeric(): void
    {
        $this->getJson($this->customerOfferingUrl(null, ['min_price' => 'abc']))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['min_price']);
    }

    public function test_browse_validates_max_price_gte_min_price(): void
    {
        $this->getJson($this->customerOfferingUrl(null, ['min_price' => 200, 'max_price' => 100]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['max_price']);
    }

    public function test_browse_validates_sort_by_allowed_values(): void
    {
        $this->getJson($this->customerOfferingUrl(null, ['sort_by' => 'invalid_field']))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['sort_by']);
    }

    public function test_browse_validates_sort_direction_allowed_values(): void
    {
        $this->getJson($this->customerOfferingUrl(null, ['sort_direction' => 'invalid']))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['sort_direction']);
    }

    // ===== Caching Tests =====

    public function test_browse_offerings_results_are_cached(): void
    {
        Offering::factory()->count(5)->forUser($this->agency->id)->create();
        Cache::flush();

        // First request (should cache)
        $response1 = $this->getJson($this->customerOfferingsUrl);

        // Modify database
        Offering::factory()->forUser($this->agency->id)->create();

        // Second request (should hit cache)
        $response2 = $this->getJson($this->customerOfferingsUrl);

        // Both responses should be identical (from cache)
        $this->assertEquals($response1->json('data.data'), $response2->json('data.data'));
        $this->assertCount(5, $response2->json('data.data')); // Still 5, not 6
    }

    public function test_different_filter_combinations_have_different_cache_keys(): void
    {
        Offering::factory()->forUser($this->agency->id)->create(['price' => 100]);
        Offering::factory()->forUser($this->agency->id)->create(['price' => 200]);
        Cache::flush();

        $response1 = $this->getJson($this->customerOfferingUrl(null, ['min_price' => 50]));
        $response2 = $this->getJson($this->customerOfferingUrl(null, ['min_price' => 150]));

        // Different results (different cache keys)
        $this->assertCount(2, $response1->json('data.data'));
        $this->assertCount(1, $response2->json('data.data'));
    }
}
