<?php

namespace Tests\Feature\Agency;

use App\Offering\Models\Offering;
use App\Offering\Models\OfferingDay;
use App\Auth\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Feature\Traits\AuthenticationHelpers;
use Tests\Feature\Traits\ResponseHelpers;
use Tests\TestCase;

class OfferingDayTest extends TestCase
{
    use AuthenticationHelpers, RefreshDatabase, ResponseHelpers;

    protected User $agency;

    protected Offering $offering;

    protected function setUp(): void
    {
        parent::setUp();
        $this->agency = $this->actingAsAgency();
        $this->offering = Offering::factory()->forUser($this->agency->id)->create();
    }

    // ===== CREATE Tests =====

    public function test_agency_can_create_offering_day(): void
    {
        $data = [
            'offering_id' => $this->offering->id,
            'date' => now()->addDays(5)->format('Y-m-d'),
            'notes' => 'Special availability',
        ];

        $response = $this->postJson('/api/agency/offering-days', $data);

        $this->assertStandardResponse($response, 201);
        $this->assertDatabaseHas('offering_days', [
            'offering_id' => $this->offering->id,
            'notes' => $data['notes'],
        ]);

        $offeringDay = OfferingDay::where('offering_id', $this->offering->id)->latest()->first();
        $this->assertEquals($data['date'], $offeringDay->date->format('Y-m-d'));
    }

    public function test_agency_can_create_offering_day_without_notes(): void
    {
        $data = [
            'offering_id' => $this->offering->id,
            'date' => date('Y-m-d', strtotime('+1 day')),
        ];

        $response = $this->postJson('/api/agency/offering-days', $data);

        $this->assertStandardResponse($response, 201);
        $this->assertDatabaseHas('offering_days', [
            'offering_id' => $this->offering->id,
            'notes' => null,
        ]);

        $offeringDay = OfferingDay::where('offering_id', $this->offering->id)->latest()->first();
        $this->assertEquals($data['date'], $offeringDay->date->format('Y-m-d'));
    }

    // ===== VALIDATION Tests =====

    #[DataProvider('invalidOfferingDayDataProvider')]
    public function test_create_offering_day_validation(array $invalidData, array $expectedErrors): void
    {
        // Merge with offering_id if we're not testing offering_id validation
        if (! in_array('offering_id', $expectedErrors) && ! isset($invalidData['offering_id'])) {
            $invalidData['offering_id'] = $this->offering->id;
        }

        $response = $this->postJson('/api/agency/offering-days', $invalidData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors($expectedErrors);
    }

    public static function invalidOfferingDayDataProvider(): array
    {
        return [
            'missing offering_id' => [
                ['date' => date('Y-m-d', strtotime('+1 day'))],
                ['offering_id'],
            ],
            'nonexistent offering_id' => [
                ['offering_id' => 99999, 'date' => date('Y-m-d', strtotime('+1 day'))],
                ['offering_id'],
            ],
            'missing date' => [
                [],
                ['date'],
            ],
            'invalid date format' => [
                ['date' => 'not-a-date'],
                ['date'],
            ],
            'date in past' => [
                ['date' => date('Y-m-d', strtotime('-1 day'))],
                ['date'],
            ],
            'notes too long' => [
                ['date' => date('Y-m-d', strtotime('+1 day')), 'notes' => str_repeat('a', 1001)],
                ['notes'],
            ],
        ];
    }

    public function test_date_can_be_today(): void
    {
        $response = $this->postJson('/api/agency/offering-days', [
            'offering_id' => $this->offering->id,
            'date' => now()->format('Y-m-d'),
        ]);

        $response->assertStatus(201);
    }

    // ===== LIST Tests =====

    public function test_agency_can_list_offering_days(): void
    {
        OfferingDay::factory()->forOffering($this->offering->id)->count(3)->create();

        $response = $this->getJson('/api/agency/offering-days?offering_id='.$this->offering->id);

        $this->assertStandardResponse($response);
        $this->assertCount(3, $response->json('data'));
    }

    public function test_listing_requires_offering_id(): void
    {
        $response = $this->getJson('/api/agency/offering-days');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['offering_id']);
    }

    public function test_agency_only_sees_their_offering_days(): void
    {
        $otherAgency = $this->createAgencyUser();
        $otherOffering = Offering::factory()->forUser($otherAgency->id)->create();

        OfferingDay::factory()->forOffering($this->offering->id)->count(3)->create();
        OfferingDay::factory()->forOffering($otherOffering->id)->count(2)->create();

        $response = $this->getJson('/api/agency/offering-days?offering_id='.$this->offering->id);

        $this->assertStandardResponse($response);
        $this->assertCount(3, $response->json('data'));
    }

    // ===== UPDATE Tests =====

    public function test_agency_can_update_offering_day(): void
    {
        $offeringDay = OfferingDay::factory()->forOffering($this->offering->id)->create();

        $newDate = now()->addDays(10)->format('Y-m-d');
        $response = $this->putJson("/api/agency/offering-days/{$offeringDay->id}", [
            'date' => $newDate,
            'notes' => 'Updated notes',
        ]);

        $response->assertStatus(204);
        $this->assertDatabaseHas('offering_days', [
            'id' => $offeringDay->id,
            'date' => $newDate,
            'notes' => 'Updated notes',
        ]);
    }

    public function test_agency_cannot_update_another_agencys_offering_day(): void
    {
        $otherAgency = $this->createAgencyUser();
        $otherOffering = Offering::factory()->forUser($otherAgency->id)->create();
        $otherOfferingDay = OfferingDay::factory()->forOffering($otherOffering->id)->create();

        $response = $this->putJson("/api/agency/offering-days/{$otherOfferingDay->id}", [
            'date' => now()->addDays(5)->format('Y-m-d'),
        ]);

        $response->assertStatus(401);
        $this->assertEquals('You are not allowed to access this page.', $response->json('errorMessage'));
    }

    public function test_updating_nonexistent_offering_day_returns_404(): void
    {
        $response = $this->putJson('/api/agency/offering-days/99999', [
            'date' => date('Y-m-d', strtotime('+1 day')),
        ]);

        $response->assertStatus(404);
        $this->assertEquals('Offering day not found', $response->json('errorMessage'));
    }

    // ===== DELETE Tests =====

    public function test_agency_can_delete_offering_day(): void
    {
        $offeringDay = OfferingDay::factory()->forOffering($this->offering->id)->create();

        $response = $this->deleteJson("/api/agency/offering-days/{$offeringDay->id}");

        $response->assertStatus(204);
        $this->assertSoftDeleted('offering_days', ['id' => $offeringDay->id]);
    }

    public function test_agency_cannot_delete_another_agencys_offering_day(): void
    {
        $otherAgency = $this->createAgencyUser();
        $otherOffering = Offering::factory()->forUser($otherAgency->id)->create();
        $otherOfferingDay = OfferingDay::factory()->forOffering($otherOffering->id)->create();

        $response = $this->deleteJson("/api/agency/offering-days/{$otherOfferingDay->id}");

        $response->assertStatus(401);
        $this->assertEquals('You are not allowed to access this page.', $response->json('errorMessage'));
    }

    public function test_deleting_nonexistent_offering_day_returns_404(): void
    {
        $response = $this->deleteJson('/api/agency/offering-days/99999');

        $response->assertStatus(404);
        $this->assertEquals('Offering day not found', $response->json('errorMessage'));
    }

    // ===== AUTHORIZATION Tests =====

    public function test_unauthenticated_user_cannot_access_offering_day_endpoints(): void
    {
        $this->actingAsUnauthenticated();

        $routes = [
            ['POST', '/api/agency/offering-days', ['offering_id' => 1, 'date' => now()->format('Y-m-d')]],
            ['GET', '/api/agency/offering-days?offering_id=1', []],
            ['PUT', '/api/agency/offering-days/1', ['date' => now()->format('Y-m-d')]],
            ['DELETE', '/api/agency/offering-days/1', []],
        ];

        foreach ($routes as [$method, $uri, $data]) {
            $response = $this->json($method, $uri, $data);
            $response->assertStatus(401);
            $this->assertEquals('Unauthenticated.', $response->json('message'));
        }
    }

    public function test_customer_cannot_access_offering_day_endpoints(): void
    {
        $this->actingAsCustomer();

        $routes = [
            ['POST', '/api/agency/offering-days', ['offering_id' => 1, 'date' => now()->format('Y-m-d')]],
            ['GET', '/api/agency/offering-days?offering_id=1', []],
            ['PUT', '/api/agency/offering-days/1', ['date' => now()->format('Y-m-d')]],
            ['DELETE', '/api/agency/offering-days/1', []],
        ];

        foreach ($routes as [$method, $uri, $data]) {
            $response = $this->json($method, $uri, $data);
            $response->assertStatus(401);
            $this->assertEquals('You are not allowed to access this page.', $response->json('errorMessage'));
        }
    }

    public function test_agency_cannot_create_day_for_another_agencys_offering(): void
    {
        $otherAgency = $this->createAgencyUser();
        $otherOffering = Offering::factory()->forUser($otherAgency->id)->create();

        $response = $this->postJson('/api/agency/offering-days', [
            'offering_id' => $otherOffering->id,
            'date' => date('Y-m-d', strtotime('+1 day')),
        ]);

        $response->assertStatus(404);
        $this->assertEquals('Offering not found.', $response->json('errorMessage'));
    }

    public function test_agency_cannot_list_days_for_another_agencys_offering(): void
    {
        $otherAgency = $this->createAgencyUser();
        $otherOffering = Offering::factory()->forUser($otherAgency->id)->create();

        $response = $this->getJson('/api/agency/offering-days?offering_id='.$otherOffering->id);

        $response->assertStatus(404);
        $this->assertEquals('Offering not found.', $response->json('errorMessage'));
    }
}
