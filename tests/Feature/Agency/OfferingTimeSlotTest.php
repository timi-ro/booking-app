<?php

namespace Tests\Feature\Agency;

use App\Auth\Models\User;
use App\Offering\Models\Offering;
use App\Offering\Models\OfferingDay;
use App\Offering\Models\OfferingTimeSlot;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Feature\Traits\AuthenticationHelpers;
use Tests\Feature\Traits\ResponseHelpers;
use Tests\TestCase;

class OfferingTimeSlotTest extends TestCase
{
    use AuthenticationHelpers, RefreshDatabase, ResponseHelpers;

    protected User $agency;

    protected Offering $offering;

    protected OfferingDay $offeringDay;

    protected function setUp(): void
    {
        parent::setUp();
        $this->agency = $this->actingAsAgency();
        $this->offering = Offering::factory()->forUser($this->agency->id)->create();
        $this->offeringDay = OfferingDay::factory()->forOffering($this->offering->id)->create();
    }

    // ===== CREATE Tests =====

    public function test_agency_can_create_time_slot(): void
    {
        $data = [
            'offering_day_id' => $this->offeringDay->id,
            'start_time' => '09:00',
            'end_time' => '10:00',
            'capacity' => 10,
            'price_override' => 150.00,
        ];

        $response = $this->postJson('/api/agency/time-slots', $data);

        $this->assertStandardResponse($response, 201);
        $this->assertDatabaseHas('offering_time_slots', [
            'offering_day_id' => $this->offeringDay->id,
            'offering_id' => $this->offering->id,
            'start_time' => '09:00',
            'end_time' => '10:00',
            'capacity' => 10,
            'price_override' => 150.00,
        ]);
    }

    public function test_agency_can_create_time_slot_without_optional_fields(): void
    {
        $data = [
            'offering_day_id' => $this->offeringDay->id,
            'start_time' => '09:00',
            'end_time' => '10:00',
        ];

        $response = $this->postJson('/api/agency/time-slots', $data);

        $this->assertStandardResponse($response, 201);
        $this->assertDatabaseHas('offering_time_slots', [
            'offering_day_id' => $this->offeringDay->id,
            'start_time' => '09:00',
            'end_time' => '10:00',
            'price_override' => null,
        ]);
    }

    // ===== BULK CREATE Tests =====

    public function test_agency_can_bulk_create_time_slots(): void
    {
        $data = [
            'offering_day_id' => $this->offeringDay->id,
            'time_slots' => [
                ['start_time' => '09:00', 'end_time' => '10:00', 'capacity' => 5],
                ['start_time' => '10:00', 'end_time' => '11:00', 'capacity' => 5],
                ['start_time' => '11:00', 'end_time' => '12:00', 'capacity' => 5],
            ],
        ];

        $response = $this->postJson('/api/agency/time-slots/bulk', $data);

        $this->assertStandardResponse($response, 201);
        $this->assertCount(3, $response->json('data'));

        foreach ($data['time_slots'] as $slot) {
            $this->assertDatabaseHas('offering_time_slots', [
                'offering_day_id' => $this->offeringDay->id,
                'start_time' => $slot['start_time'],
                'end_time' => $slot['end_time'],
                'capacity' => $slot['capacity'],
            ]);
        }
    }

    public function test_bulk_create_with_price_overrides(): void
    {
        $data = [
            'offering_day_id' => $this->offeringDay->id,
            'time_slots' => [
                ['start_time' => '09:00', 'end_time' => '10:00', 'capacity' => 5, 'price_override' => 100.00],
                ['start_time' => '10:00', 'end_time' => '11:00', 'capacity' => 5, 'price_override' => 150.00],
            ],
        ];

        $response = $this->postJson('/api/agency/time-slots/bulk', $data);

        $this->assertStandardResponse($response, 201);
        $this->assertDatabaseHas('offering_time_slots', [
            'start_time' => '09:00',
            'price_override' => 100.00,
        ]);
        $this->assertDatabaseHas('offering_time_slots', [
            'start_time' => '10:00',
            'price_override' => 150.00,
        ]);
    }

    public function test_bulk_create_requires_time_slots_array(): void
    {
        $response = $this->postJson('/api/agency/time-slots/bulk', [
            'offering_day_id' => $this->offeringDay->id,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['time_slots']);
    }

    public function test_bulk_create_requires_at_least_one_slot(): void
    {
        $response = $this->postJson('/api/agency/time-slots/bulk', [
            'offering_day_id' => $this->offeringDay->id,
            'time_slots' => [],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['time_slots']);
    }

    // ===== VALIDATION Tests =====

    #[DataProvider('invalidTimeSlotDataProvider')]
    public function test_create_time_slot_validation(array $invalidData, array $expectedErrors): void
    {
        // Add base fields if not testing them
        $baseData = [
            'offering_day_id' => $this->offeringDay->id,
            'start_time' => '09:00',
            'end_time' => '10:00',
        ];

        // Merge, giving priority to invalidData
        $data = array_merge($baseData, $invalidData);

        $response = $this->postJson('/api/agency/time-slots', $data);

        $response->assertStatus(422)
            ->assertJsonValidationErrors($expectedErrors);
    }

    public static function invalidTimeSlotDataProvider(): array
    {
        return [
            'missing offering_day_id' => [
                ['offering_day_id' => null],
                ['offering_day_id'],
            ],
            'nonexistent offering_day_id' => [
                ['offering_day_id' => 99999],
                ['offering_day_id'],
            ],
            'missing start_time' => [
                ['start_time' => null],
                ['start_time'],
            ],
            'invalid start_time format: 9am' => [
                ['start_time' => '9am'],
                ['start_time'],
            ],
            'invalid start_time format: 9:00am' => [
                ['start_time' => '9:00am'],
                ['start_time'],
            ],
            'invalid start_time format: 09:00:00' => [
                ['start_time' => '09:00:00'],
                ['start_time'],
            ],
            'invalid start_time format: invalid' => [
                ['start_time' => 'invalid'],
                ['start_time'],
            ],
            'missing end_time' => [
                ['end_time' => null],
                ['end_time'],
            ],
            'end_time before start_time' => [
                ['start_time' => '10:00', 'end_time' => '09:00'],
                ['end_time'],
            ],
            'end_time equals start_time' => [
                ['start_time' => '09:00', 'end_time' => '09:00'],
                ['end_time'],
            ],
            'capacity less than 1' => [
                ['capacity' => 0],
                ['capacity'],
            ],
            'capacity exceeds maximum' => [
                ['capacity' => 1001],
                ['capacity'],
            ],
            'negative price_override' => [
                ['price_override' => -10.00],
                ['price_override'],
            ],
        ];
    }

    public function test_price_override_can_be_zero(): void
    {
        $response = $this->postJson('/api/agency/time-slots', [
            'offering_day_id' => $this->offeringDay->id,
            'start_time' => '09:00',
            'end_time' => '10:00',
            'price_override' => 0,
        ]);

        $response->assertStatus(201);
    }

    // ===== LIST Tests =====

    public function test_agency_can_list_time_slots(): void
    {
        OfferingTimeSlot::factory()->forOfferingDay($this->offeringDay->id)->count(3)->create();

        $response = $this->getJson('/api/agency/time-slots?offering_day_id='.$this->offeringDay->id);

        $this->assertStandardResponse($response);
        $this->assertCount(3, $response->json('data'));
    }

    public function test_listing_requires_offering_day_id(): void
    {
        $response = $this->getJson('/api/agency/time-slots');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['offering_day_id']);
    }

    public function test_agency_only_sees_their_time_slots(): void
    {
        $otherAgency = $this->createAgencyUser();
        $otherOffering = Offering::factory()->forUser($otherAgency->id)->create();
        $otherDay = OfferingDay::factory()->forOffering($otherOffering->id)->create();

        OfferingTimeSlot::factory()->forOfferingDay($this->offeringDay->id)->count(3)->create();
        OfferingTimeSlot::factory()->forOfferingDay($otherDay->id)->count(2)->create();

        $response = $this->getJson('/api/agency/time-slots?offering_day_id='.$this->offeringDay->id);

        $this->assertStandardResponse($response);
        $this->assertCount(3, $response->json('data'));
    }

    // ===== UPDATE Tests =====

    public function test_agency_can_update_time_slot(): void
    {
        $timeSlot = OfferingTimeSlot::factory()->forOfferingDay($this->offeringDay->id)->create();

        $response = $this->putJson("/api/agency/time-slots/{$timeSlot->id}", [
            'start_time' => '14:00',
            'end_time' => '15:00',
            'capacity' => 20,
            'price_override' => 200.00,
        ]);

        $response->assertStatus(204);
        $this->assertDatabaseHas('offering_time_slots', [
            'id' => $timeSlot->id,
            'start_time' => '14:00',
            'end_time' => '15:00',
            'capacity' => 20,
            'price_override' => 200.00,
        ]);
    }

    public function test_agency_cannot_update_another_agencys_time_slot(): void
    {
        $otherAgency = $this->createAgencyUser();
        $otherOffering = Offering::factory()->forUser($otherAgency->id)->create();
        $otherDay = OfferingDay::factory()->forOffering($otherOffering->id)->create();
        $otherSlot = OfferingTimeSlot::factory()->forOfferingDay($otherDay->id)->create();

        $response = $this->putJson("/api/agency/time-slots/{$otherSlot->id}", [
            'start_time' => '14:00',
            'end_time' => '15:00',
        ]);

        $response->assertStatus(401);
        $this->assertEquals('You are not allowed to access this page.', $response->json('errorMessage'));
    }

    public function test_updating_nonexistent_time_slot_returns_404(): void
    {
        $response = $this->putJson('/api/agency/time-slots/99999', [
            'start_time' => '14:00',
            'end_time' => '15:00',
        ]);

        $response->assertStatus(404);
        $this->assertEquals('Time slot not found', $response->json('errorMessage'));
    }

    // ===== DELETE Tests =====

    public function test_agency_can_delete_time_slot(): void
    {
        $timeSlot = OfferingTimeSlot::factory()->forOfferingDay($this->offeringDay->id)->create();

        $response = $this->deleteJson("/api/agency/time-slots/{$timeSlot->id}");

        $response->assertStatus(204);
        $this->assertSoftDeleted('offering_time_slots', ['id' => $timeSlot->id]);
    }

    public function test_agency_cannot_delete_another_agencys_time_slot(): void
    {
        $otherAgency = $this->createAgencyUser();
        $otherOffering = Offering::factory()->forUser($otherAgency->id)->create();
        $otherDay = OfferingDay::factory()->forOffering($otherOffering->id)->create();
        $otherSlot = OfferingTimeSlot::factory()->forOfferingDay($otherDay->id)->create();

        $response = $this->deleteJson("/api/agency/time-slots/{$otherSlot->id}");

        $response->assertStatus(401);
        $this->assertEquals('You are not allowed to access this page.', $response->json('errorMessage'));
    }

    public function test_deleting_nonexistent_time_slot_returns_404(): void
    {
        $response = $this->deleteJson('/api/agency/time-slots/99999');

        $response->assertStatus(404);
        $this->assertEquals('Time slot not found', $response->json('errorMessage'));
    }

    // ===== AUTHORIZATION Tests =====

    public function test_unauthenticated_user_cannot_access_time_slot_endpoints(): void
    {
        $this->actingAsUnauthenticated();

        $routes = [
            ['POST', '/api/agency/time-slots', ['offering_day_id' => 1, 'start_time' => '09:00', 'end_time' => '10:00']],
            ['POST', '/api/agency/time-slots/bulk', ['offering_day_id' => 1, 'time_slots' => []]],
            ['GET', '/api/agency/time-slots?offering_day_id=1', []],
            ['PUT', '/api/agency/time-slots/1', ['start_time' => '09:00', 'end_time' => '10:00']],
            ['DELETE', '/api/agency/time-slots/1', []],
        ];

        foreach ($routes as [$method, $uri, $data]) {
            $response = $this->json($method, $uri, $data);
            $response->assertStatus(401);
            $this->assertEquals('Unauthenticated.', $response->json('message'));
        }
    }

    public function test_customer_cannot_access_time_slot_endpoints(): void
    {
        $this->actingAsCustomer();

        $routes = [
            ['POST', '/api/agency/time-slots', ['offering_day_id' => 1, 'start_time' => '09:00', 'end_time' => '10:00']],
            ['POST', '/api/agency/time-slots/bulk', ['offering_day_id' => 1, 'time_slots' => []]],
            ['GET', '/api/agency/time-slots?offering_day_id=1', []],
            ['PUT', '/api/agency/time-slots/1', ['start_time' => '09:00', 'end_time' => '10:00']],
            ['DELETE', '/api/agency/time-slots/1', []],
        ];

        foreach ($routes as [$method, $uri, $data]) {
            $response = $this->json($method, $uri, $data);
            $response->assertStatus(401);
            $this->assertEquals('You are not allowed to access this page.', $response->json('errorMessage'));
        }
    }

    public function test_agency_cannot_create_slot_for_another_agencys_offering_day(): void
    {
        $otherAgency = $this->createAgencyUser();
        $otherOffering = Offering::factory()->forUser($otherAgency->id)->create();
        $otherDay = OfferingDay::factory()->forOffering($otherOffering->id)->create();

        $response = $this->postJson('/api/agency/time-slots', [
            'offering_day_id' => $otherDay->id,
            'start_time' => '09:00',
            'end_time' => '10:00',
        ]);

        $response->assertStatus(401);
        $this->assertEquals('You are not allowed to access this page.', $response->json('errorMessage'));
    }

    public function test_agency_cannot_bulk_create_slots_for_another_agencys_offering_day(): void
    {
        $otherAgency = $this->createAgencyUser();
        $otherOffering = Offering::factory()->forUser($otherAgency->id)->create();
        $otherDay = OfferingDay::factory()->forOffering($otherOffering->id)->create();

        $response = $this->postJson('/api/agency/time-slots/bulk', [
            'offering_day_id' => $otherDay->id,
            'time_slots' => [
                ['start_time' => '09:00', 'end_time' => '10:00', 'capacity' => 5],
            ],
        ]);

        $response->assertStatus(401);
        $this->assertEquals('You are not allowed to access this page.', $response->json('errorMessage'));
    }

    public function test_agency_cannot_list_slots_for_another_agencys_offering_day(): void
    {
        $otherAgency = $this->createAgencyUser();
        $otherOffering = Offering::factory()->forUser($otherAgency->id)->create();
        $otherDay = OfferingDay::factory()->forOffering($otherOffering->id)->create();

        $response = $this->getJson('/api/agency/time-slots?offering_day_id='.$otherDay->id);

        $response->assertStatus(401);
        $this->assertEquals('You are not allowed to access this page.', $response->json('errorMessage'));
    }
}
