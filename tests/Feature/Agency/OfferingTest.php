<?php

namespace Tests\Feature\Agency;

use App\Auth\Models\User;
use App\Offering\Models\Offering;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Feature\Traits\AuthenticationHelpers;
use Tests\Feature\Traits\OfferingTestHelpers;
use Tests\Feature\Traits\ResponseHelpers;
use Tests\TestCase;

class OfferingTest extends TestCase
{
    use AuthenticationHelpers, OfferingTestHelpers, RefreshDatabase, ResponseHelpers;

    protected User $agency;

    protected function setUp(): void
    {
        parent::setUp();

        $this->agency = $this->actingAsAgency();
    }

    // ===== CREATE Offering Tests =====

    public function test_agency_can_create_offering_with_valid_data(): void
    {
        $response = $this->postJson($this->agencyOfferingsUrl, $this->getValidOfferingData());

        $this->assertStandardResponse($response, 201);
        $this->assertOfferingStructure($response);
        $this->assertDatabaseHas('offerings', [
            'title' => 'Test Offering',
            'user_id' => $this->agency->id,
        ]);
    }

    #[DataProvider('invalidOfferingDataProvider')]
    public function test_create_offering_validation(array $invalidData, array $expectedErrors): void
    {
        $response = $this->postJson($this->agencyOfferingsUrl, $invalidData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors($expectedErrors);
    }

    public static function invalidOfferingDataProvider(): array
    {
        $base = [
            'title' => 'Valid Title',
            'description' => 'Valid Description',
            'price' => 100.00,
            'address_info' => 'Valid Address',
        ];

        return [
            'missing title' => [array_merge($base, ['title' => '']), ['title']],
            'title too long' => [array_merge($base, ['title' => str_repeat('a', 256)]), ['title']],
            'title whitespace only' => [array_merge($base, ['title' => '   ']), ['title']],
            'missing description' => [array_merge($base, ['description' => '']), ['description']],
            'description too long' => [array_merge($base, ['description' => str_repeat('a', 5001)]), ['description']],
            'description whitespace only' => [array_merge($base, ['description' => '   ']), ['description']],
            'missing price' => [['title' => 'Test', 'description' => 'Test', 'address_info' => 'Test'], ['price']],
            'price not numeric' => [array_merge($base, ['price' => 'not-a-number']), ['price']],
            'price negative' => [array_merge($base, ['price' => -10]), ['price']],
            'price zero' => [array_merge($base, ['price' => 0]), ['price']],
            'missing address_info' => [array_merge($base, ['address_info' => '']), ['address_info']],
            'address_info too long' => [array_merge($base, ['address_info' => str_repeat('a', 5001)]), ['address_info']],
            'address_info whitespace only' => [array_merge($base, ['address_info' => '   ']), ['address_info']],
        ];
    }

    // ===== LIST Offerings Tests =====

    public function test_agency_can_list_their_offerings(): void
    {
        $myOfferings = Offering::factory()->count(5)->forUser($this->agency->id)->create();
        Offering::factory()->count(3)->forUser($this->createAgencyUser()->id)->create();

        $response = $this->getJson($this->agencyOfferingsUrl);

        $this->assertStandardResponse($response);
        $data = $response->json('data.data');
        $this->assertCount(5, $data);
        $this->assertAllOfferingsBelongToUser($data, $this->agency);

        $returnedIds = collect($data)->pluck('id')->all();
        $expectedIds = $myOfferings->pluck('id')->all();
        $this->assertEqualsCanonicalizing($expectedIds, $returnedIds);
    }

    public function test_agency_listings_are_paginated(): void
    {
        Offering::factory()->count(25)->forUser($this->agency->id)->create();

        $response = $this->getJson($this->agencyOfferingUrl(null, ['page' => 1, 'page_size' => 10]));

        $this->assertStandardResponse($response);
        $this->assertEquals(10, count($response->json('data.data')));
        $this->assertPaginationMetadata($response, 1, 25, 10);
    }

    public function test_empty_offerings_list_returns_successfully(): void
    {
        $response = $this->getJson($this->agencyOfferingsUrl);

        $this->assertStandardResponse($response);
        $this->assertEmpty($response->json('data.data'));
    }

    // ===== UPDATE Offerings Tests =====

    public function test_agency_can_update_their_offering(): void
    {
        $offering = Offering::factory()->forUser($this->agency->id)->create(['title' => 'Original']);

        $response = $this->putJson($this->agencyOfferingUrl($offering->id), ['title' => 'Updated']);

        $response->assertStatus(204);
        $this->assertDatabaseHas('offerings', [
            'id' => $offering->id,
            'title' => 'Updated',
        ]);
    }

    public function test_agency_can_partially_update_offering(): void
    {
        $offering = Offering::factory()->forUser($this->agency->id)->create([
            'title' => 'Original',
            'description' => 'Original Description',
            'price' => 100,
        ]);

        $response = $this->putJson($this->agencyOfferingUrl($offering->id), ['title' => 'New']);

        $response->assertStatus(204);
        $this->assertDatabaseHas('offerings', [
            'id' => $offering->id,
            'title' => 'New',
            'description' => 'Original Description',
            'price' => 100,
        ]);
    }

    public function test_agency_cannot_update_another_agencys_offering(): void
    {
        $otherOffering = Offering::factory()->forUser($this->createAgencyUser()->id)->create();

        $response = $this->putJson($this->agencyOfferingUrl($otherOffering->id), ['title' => 'Hacked']);

        $response->assertStatus(404);
        $this->assertDatabaseMissing('offerings', ['title' => 'Hacked']);
    }

    public function test_update_offering_validates_input(): void
    {
        $offering = Offering::factory()->forUser($this->agency->id)->create();

        $response = $this->putJson($this->agencyOfferingUrl($offering->id), ['price' => 'invalid']);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['price']);
    }

    // ===== DELETE Offerings Tests =====

    public function test_agency_can_delete_their_offering(): void
    {
        $offering = Offering::factory()->forUser($this->agency->id)->create();

        $response = $this->deleteJson($this->agencyOfferingUrl($offering->id));

        $response->assertStatus(204);
        $this->assertSoftDeleted('offerings', ['id' => $offering->id]);
    }

    public function test_deleted_offering_not_in_listings(): void
    {
        $offering1 = Offering::factory()->forUser($this->agency->id)->create(['title' => 'Active']);
        $offering2 = Offering::factory()->forUser($this->agency->id)->create(['title' => 'Deleted']);
        $this->deleteJson($this->agencyOfferingUrl($offering2->id));

        $response = $this->getJson($this->agencyOfferingsUrl);

        $data = $response->json('data.data');
        $this->assertCount(1, $data);
        $this->assertEquals($offering1['title'], $data[0]['title']);
    }

    public function test_agency_cannot_delete_another_agencys_offering(): void
    {
        $otherOffering = Offering::factory()->forUser($this->createAgencyUser()->id)->create();

        $response = $this->deleteJson($this->agencyOfferingUrl($otherOffering->id));

        $response->assertStatus(404);
        $this->assertDatabaseHas('offerings', [
            'id' => $otherOffering->id,
            'deleted_at' => null,
        ]);
        $this->assertEquals('Offering not found.', $response->json('errorMessage'));
    }

    // ===== Authorization Tests =====

    public function test_unauthenticated_user_cannot_access_agency_endpoints(): void
    {
        $this->actingAsUnauthenticated();

        $this->postJson($this->agencyOfferingsUrl, $this->getValidOfferingData())
            ->assertStatus(401);
    }

    public function test_customer_cannot_access_agency_endpoints(): void
    {
        $this->actingAsCustomer();

        $this->postJson($this->agencyOfferingsUrl, $this->getValidOfferingData())
            ->assertStatus(401);
    }

    // ===== Error Handling Tests =====

    public function test_operations_on_nonexistent_offering_return_404(): void
    {
        $this->putJson($this->agencyOfferingUrl(999999), ['title' => 'Test'])
            ->assertStatus(404);

        $this->deleteJson($this->agencyOfferingUrl(999999))
            ->assertStatus(404);
    }
}
