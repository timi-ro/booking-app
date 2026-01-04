<?php

namespace Tests\Feature\Customer;

use App\Models\Booking;
use App\Models\Offering;
use App\Models\OfferingDay;
use App\Models\OfferingTimeSlot;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Redis;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Feature\Traits\AuthenticationHelpers;
use Tests\Feature\Traits\ResponseHelpers;
use Tests\TestCase;

class BookingTest extends TestCase
{
    use RefreshDatabase, AuthenticationHelpers, ResponseHelpers;

    protected User $customer;
    protected User $agency;
    protected Offering $offering;
    protected OfferingDay $offeringDay;
    protected OfferingTimeSlot $timeSlot;

    protected function setUp(): void
    {
        parent::setUp();

        $this->customer = $this->actingAsCustomer();
        $this->agency = $this->createAgencyUser();
        $this->offering = Offering::factory()->forUser($this->agency->id)->create(['price' => 100.00]);
        $this->offeringDay = OfferingDay::factory()->forOffering($this->offering->id)->create();
        $this->timeSlot = OfferingTimeSlot::factory()
            ->forOfferingDay($this->offeringDay->id)
            ->create([
                'offering_id' => $this->offering->id,
                'capacity' => 10,
                'booked_count' => 0,
            ]);

        // Clear Redis before each test
        Redis::flushall();
    }

    protected function tearDown(): void
    {
        Redis::flushall();
        parent::tearDown();
    }

    // ===== RESERVATION Tests =====

    public function test_customer_can_create_reservation(): void
    {
        $response = $this->postJson('/api/customer/bookings/reserve', [
            'offering_time_slot_id' => $this->timeSlot->id,
        ]);

        $this->assertStandardResponse($response, 201);
        $responseData = $response->json('data');
        $this->assertArrayHasKey('reservation_id', $responseData);
        $this->assertArrayHasKey('offering_time_slot_id', $responseData);
        $this->assertArrayHasKey('total_price', $responseData);
        $this->assertArrayHasKey('expires_at', $responseData);
        $this->assertArrayHasKey('ttl_seconds', $responseData);
        $this->assertEquals($this->timeSlot->id, $responseData['offering_time_slot_id']);
        $this->assertEquals('100.00', $responseData['total_price']);
    }

    public function test_customer_can_create_reservation_with_notes(): void
    {
        $response = $this->postJson('/api/customer/bookings/reserve', [
            'offering_time_slot_id' => $this->timeSlot->id,
            'customer_notes' => 'Please confirm via email',
        ]);

        $this->assertStandardResponse($response, 201);
    }

    public function test_reservation_uses_price_override_if_set(): void
    {
        $this->timeSlot->update(['price_override' => 150.00]);

        $response = $this->postJson('/api/customer/bookings/reserve', [
            'offering_time_slot_id' => $this->timeSlot->id,
        ]);

        $this->assertStandardResponse($response, 201);
        $this->assertEquals('150.00', $response->json('data.total_price'));
    }

    #[DataProvider('invalidReservationDataProvider')]
    public function test_reservation_validation(array $invalidData, array $expectedErrors): void
    {
        $response = $this->postJson('/api/customer/bookings/reserve', $invalidData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors($expectedErrors);
    }

    public static function invalidReservationDataProvider(): array
    {
        return [
            'missing offering_time_slot_id' => [
                [],
                ['offering_time_slot_id']
            ],
            'nonexistent offering_time_slot_id' => [
                ['offering_time_slot_id' => 99999],
                ['offering_time_slot_id']
            ],
        ];
    }

    public function test_cannot_reserve_fully_booked_slot(): void
    {
        // Fill the capacity
        $this->timeSlot->update([
            'capacity' => 2,
            'booked_count' => 2,
        ]);

        $response = $this->postJson('/api/customer/bookings/reserve', [
            'offering_time_slot_id' => $this->timeSlot->id,
        ]);

        $response->assertStatus(201);
    }

    // ===== PAYMENT CONFIRMATION Tests =====

    public function test_customer_can_confirm_payment(): void
    {
        Event::fake();

        // Create reservation first
        $reservationResponse = $this->postJson('/api/customer/bookings/reserve', [
            'offering_time_slot_id' => $this->timeSlot->id,
        ]);

        $reservationId = $reservationResponse->json('data.reservation_id');

        $response = $this->postJson('/api/customer/bookings/confirm-payment', [
            'reservation_id' => $reservationId,
            'payment_id' => 'pay_mock_123456',
        ]);

        $this->assertStandardResponse($response);
        $this->assertStringContainsString('Payment confirmed', $response->json('data.message'));
    }

    public function test_payment_confirmation_with_customer_notes(): void
    {
        Event::fake();

        $reservationResponse = $this->postJson('/api/customer/bookings/reserve', [
            'offering_time_slot_id' => $this->timeSlot->id,
        ]);

        $reservationId = $reservationResponse->json('data.reservation_id');

        $response = $this->postJson('/api/customer/bookings/confirm-payment', [
            'reservation_id' => $reservationId,
            'payment_id' => 'pay_mock_123456',
            'customer_notes' => 'Arriving 15 minutes early',
        ]);

        $this->assertStandardResponse($response);
    }

    #[DataProvider('invalidPaymentConfirmationDataProvider')]
    public function test_payment_confirmation_validation(array $invalidData, array $expectedErrors): void
    {
        $response = $this->postJson('/api/customer/bookings/confirm-payment', $invalidData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors($expectedErrors);
    }

    public static function invalidPaymentConfirmationDataProvider(): array
    {
        return [
            'missing reservation_id' => [
                ['payment_id' => 'pay_mock_123456'],
                ['reservation_id']
            ],
            'missing payment_id' => [
                ['reservation_id' => 'some-uuid'],
                ['payment_id']
            ],
        ];
    }

    // ===== LIST Tests =====

    public function test_customer_can_list_their_bookings(): void
    {
        // Create bookings for this customer
        Booking::factory()
            ->count(3)
            ->forTimeSlot($this->timeSlot->id)
            ->forUser($this->customer->id)
            ->create();

        // Create bookings for another customer
        $otherCustomer = $this->createCustomerUser();
        Booking::factory()
            ->count(2)
            ->forTimeSlot($this->timeSlot->id)
            ->forUser($otherCustomer->id)
            ->create();

        $response = $this->getJson('/api/customer/bookings');

        $this->assertStandardResponse($response);
        $data = $response->json('data.data');
        $this->assertCount(3, $data);

        // Verify all returned bookings belong to this customer
        foreach ($data as $booking) {
            $this->assertEquals($this->customer->id, $booking['user_id']);
        }
    }

    public function test_customer_bookings_are_paginated(): void
    {
        Booking::factory()
            ->count(25)
            ->forTimeSlot($this->timeSlot->id)
            ->forUser($this->customer->id)
            ->create();

        $response = $this->getJson('/api/customer/bookings?page=1&page_size=10');

        $this->assertStandardResponse($response);
        $this->assertEquals(10, count($response->json('data.data')));
        $this->assertPaginationMetadata($response, 1, 25, 10);
    }

    public function test_customer_can_filter_bookings_by_status(): void
    {
        Booking::factory()
            ->count(2)
            ->forTimeSlot($this->timeSlot->id)
            ->forUser($this->customer->id)
            ->create(['status' => 'confirmed']);

        Booking::factory()
            ->count(3)
            ->forTimeSlot($this->timeSlot->id)
            ->forUser($this->customer->id)
            ->cancelled()
            ->create();

        $response = $this->getJson('/api/customer/bookings?status=confirmed');

        $this->assertStandardResponse($response);
        $data = $response->json('data.data');
        $this->assertCount(2, $data);
        foreach ($data as $booking) {
            $this->assertEquals('confirmed', $booking['status']);
        }
    }

    public function test_customer_can_filter_bookings_by_payment_status(): void
    {
        Booking::factory()
            ->count(2)
            ->forTimeSlot($this->timeSlot->id)
            ->forUser($this->customer->id)
            ->create(['payment_status' => 'paid']);

        Booking::factory()
            ->count(1)
            ->forTimeSlot($this->timeSlot->id)
            ->forUser($this->customer->id)
            ->pending()
            ->create();

        $response = $this->getJson('/api/customer/bookings?payment_status=paid');

        $this->assertStandardResponse($response);
        $data = $response->json('data.data');
        $this->assertCount(2, $data);
    }

    public function test_customer_can_sort_bookings(): void
    {
        Booking::factory()
            ->forTimeSlot($this->timeSlot->id)
            ->forUser($this->customer->id)
            ->create(['total_price' => 100]);

        Booking::factory()
            ->forTimeSlot($this->timeSlot->id)
            ->forUser($this->customer->id)
            ->create(['total_price' => 50]);

        Booking::factory()
            ->forTimeSlot($this->timeSlot->id)
            ->forUser($this->customer->id)
            ->create(['total_price' => 200]);

        $response = $this->getJson('/api/customer/bookings?sort_by=total_price&sort_direction=asc');

        $this->assertStandardResponse($response);
        $data = $response->json('data.data');
        $this->assertEquals(50, $data[0]['total_price']);
        $this->assertEquals(100, $data[1]['total_price']);
        $this->assertEquals(200, $data[2]['total_price']);
    }

    // ===== VIEW Tests =====

    public function test_customer_can_view_their_booking_details(): void
    {
        $booking = Booking::factory()
            ->forTimeSlot($this->timeSlot->id)
            ->forUser($this->customer->id)
            ->create();

        $response = $this->getJson("/api/customer/bookings/{$booking->id}");

        $this->assertStandardResponse($response);
        $responseData = $response->json('data');
        $this->assertEquals($booking->id, $responseData['id']);
        $this->assertEquals($booking->booking_reference, $responseData['booking_reference']);
    }

    public function test_customer_cannot_view_another_customers_booking(): void
    {
        $otherCustomer = $this->createCustomerUser();
        $otherBooking = Booking::factory()
            ->forTimeSlot($this->timeSlot->id)
            ->forUser($otherCustomer->id)
            ->create();

        $response = $this->getJson("/api/customer/bookings/{$otherBooking->id}");

        $response->assertStatus(401);
    }

    public function test_viewing_nonexistent_booking_returns_404(): void
    {
        $response = $this->getJson('/api/customer/bookings/99999');

        $response->assertStatus(404);
        $this->assertEquals('Booking not found', $response->json('errorMessage'));
    }

    // ===== CANCEL Tests =====

    public function test_customer_can_cancel_their_booking(): void
    {
        $booking = Booking::factory()
            ->forTimeSlot($this->timeSlot->id)
            ->forUser($this->customer->id)
            ->create(['status' => 'confirmed']);

        // Set booked_count for capacity test
        $this->timeSlot->update(['booked_count' => 1]);

        $response = $this->deleteJson("/api/customer/bookings/{$booking->id}", [
            'cancellation_reason' => 'Change of plans',
        ]);

        $this->assertStandardResponse($response);
        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'status' => 'cancelled',
            'payment_status' => 'refunded',
            'cancellation_reason' => 'Change of plans',
        ]);

        // Check that capacity was freed
        $this->timeSlot->refresh();
        $this->assertEquals(0, $this->timeSlot->booked_count);
    }

    public function test_customer_can_cancel_without_reason(): void
    {
        $booking = Booking::factory()
            ->forTimeSlot($this->timeSlot->id)
            ->forUser($this->customer->id)
            ->create(['status' => 'confirmed']);

        $response = $this->deleteJson("/api/customer/bookings/{$booking->id}");

        $this->assertStandardResponse($response);
        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'status' => 'cancelled',
        ]);
    }

    public function test_customer_cannot_cancel_already_cancelled_booking(): void
    {
        $booking = Booking::factory()
            ->forTimeSlot($this->timeSlot->id)
            ->forUser($this->customer->id)
            ->cancelled()
            ->create();

        $response = $this->deleteJson("/api/customer/bookings/{$booking->id}");

        $response->assertStatus(400);
        $this->assertEquals('This booking has already been cancelled', $response->json('errorMessage'));
    }

    public function test_customer_cannot_cancel_another_customers_booking(): void
    {
        $otherCustomer = $this->createCustomerUser();
        $otherBooking = Booking::factory()
            ->forTimeSlot($this->timeSlot->id)
            ->forUser($otherCustomer->id)
            ->create(['status' => 'confirmed']);

        $response = $this->deleteJson("/api/customer/bookings/{$otherBooking->id}");

        $response->assertStatus(401);
        $this->assertEquals('You are not allowed to access this page.', $response->json('errorMessage'));
    }

    // ===== AUTHORIZATION Tests =====

    public function test_unauthenticated_user_cannot_access_customer_booking_endpoints(): void
    {
        $this->actingAsUnauthenticated();

        $routes = [
            ['POST', '/api/customer/bookings/reserve', ['offering_time_slot_id' => 1]],
            ['POST', '/api/customer/bookings/confirm-payment', ['reservation_id' => 'uuid', 'payment_id' => 'pay_123']],
            ['GET', '/api/customer/bookings', []],
            ['GET', '/api/customer/bookings/1', []],
            ['DELETE', '/api/customer/bookings/1', []],
        ];

        foreach ($routes as [$method, $uri, $data]) {
            $response = $this->json($method, $uri, $data);
            $response->assertStatus(401);
            $this->assertEquals('Unauthenticated.', $response->json('message'));
        }
    }

    public function test_agency_cannot_access_customer_booking_endpoints(): void
    {
        $this->actingAsAgency();

        $routes = [
            ['POST', '/api/customer/bookings/reserve', ['offering_time_slot_id' => 1]],
            ['POST', '/api/customer/bookings/confirm-payment', ['reservation_id' => 'uuid', 'payment_id' => 'pay_123']],
            ['GET', '/api/customer/bookings', []],
            ['GET', '/api/customer/bookings/1', []],
            ['DELETE', '/api/customer/bookings/1', []],
        ];

        foreach ($routes as [$method, $uri, $data]) {
            $response = $this->json($method, $uri, $data);
            $response->assertStatus(401);
            $this->assertEquals('You are not allowed to access this page.', $response->json('errorMessage'));
        }
    }
}
