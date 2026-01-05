<?php

namespace Tests\Feature\Agency;

use App\Models\Booking;
use App\Models\Offering;
use App\Models\OfferingDay;
use App\Models\OfferingTimeSlot;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Traits\AuthenticationHelpers;
use Tests\Feature\Traits\ResponseHelpers;
use Tests\TestCase;

class BookingTest extends TestCase
{
    use RefreshDatabase, AuthenticationHelpers, ResponseHelpers;

    protected User $agency;
    protected Offering $offering;
    protected OfferingDay $offeringDay;
    protected OfferingTimeSlot $timeSlot;

    protected function setUp(): void
    {
        parent::setUp();

        $this->agency = $this->actingAsAgency();
        $this->offering = Offering::factory()->forUser($this->agency->id)->create();
        $this->offeringDay = OfferingDay::factory()->forOffering($this->offering->id)->create();
        $this->timeSlot = OfferingTimeSlot::factory()
            ->forOfferingDay($this->offeringDay->id)
            ->create(['offering_id' => $this->offering->id]);
    }

    // ===== LIST Tests =====

    public function test_agency_can_list_their_bookings(): void
    {
        $customer = $this->createCustomerUser();

        Booking::factory()
            ->count(3)
            ->forTimeSlot($this->timeSlot->id)
            ->forUser($customer->id)
            ->create();

        $otherAgency = $this->createAgencyUser();
        $otherOffering = Offering::factory()->forUser($otherAgency->id)->create();
        $otherDay = OfferingDay::factory()->forOffering($otherOffering->id)->create();
        $otherSlot = OfferingTimeSlot::factory()
            ->forOfferingDay($otherDay->id)
            ->create(['offering_id' => $otherOffering->id]);
        Booking::factory()
            ->count(2)
            ->forTimeSlot($otherSlot->id)
            ->forUser($customer->id)
            ->create();

        $response = $this->getJson('/api/agency/bookings');

        $this->assertStandardResponse($response);
        $data = $response->json('data.data');
        $this->assertCount(3, $data);
    }

    public function test_agency_bookings_are_paginated(): void
    {
        $customer = $this->createCustomerUser();

        Booking::factory()
            ->count(25)
            ->forTimeSlot($this->timeSlot->id)
            ->forUser($customer->id)
            ->create();

        $response = $this->getJson('/api/agency/bookings?page=1&page_size=10');

        $this->assertStandardResponse($response);
        $this->assertEquals(10, count($response->json('data.data')));
        $this->assertPaginationMetadata($response, 1, 25, 10);
    }

    public function test_agency_can_filter_bookings_by_status(): void
    {
        $customer = $this->createCustomerUser();

        Booking::factory()
            ->count(2)
            ->forTimeSlot($this->timeSlot->id)
            ->forUser($customer->id)
            ->create(['status' => 'confirmed']);

        Booking::factory()
            ->count(3)
            ->forTimeSlot($this->timeSlot->id)
            ->forUser($customer->id)
            ->cancelled()
            ->create();

        $response = $this->getJson('/api/agency/bookings?status=confirmed');

        $this->assertStandardResponse($response);
        $data = $response->json('data.data');
        $this->assertCount(2, $data);
        foreach ($data as $booking) {
            $this->assertEquals('confirmed', $booking['status']);
        }
    }

    public function test_agency_can_filter_bookings_by_payment_status(): void
    {
        $customer = $this->createCustomerUser();

        Booking::factory()
            ->count(2)
            ->forTimeSlot($this->timeSlot->id)
            ->forUser($customer->id)
            ->create(['payment_status' => 'paid']);

        Booking::factory()
            ->count(1)
            ->forTimeSlot($this->timeSlot->id)
            ->forUser($customer->id)
            ->pending()
            ->create();

        $response = $this->getJson('/api/agency/bookings?payment_status=paid');

        $this->assertStandardResponse($response);
        $data = $response->json('data.data');
        $this->assertCount(2, $data);
        foreach ($data as $booking) {
            $this->assertEquals('paid', $booking['payment_status']);
        }
    }

    public function test_agency_can_filter_bookings_by_offering_id(): void
    {
        $customer = $this->createCustomerUser();

        Booking::factory()
            ->count(2)
            ->forTimeSlot($this->timeSlot->id)
            ->forUser($customer->id)
            ->create();

        $offering2 = Offering::factory()->forUser($this->agency->id)->create();
        $day2 = OfferingDay::factory()->forOffering($offering2->id)->create();
        $slot2 = OfferingTimeSlot::factory()
            ->forOfferingDay($day2->id)
            ->create(['offering_id' => $offering2->id]);
        Booking::factory()
            ->count(3)
            ->forTimeSlot($slot2->id)
            ->forUser($customer->id)
            ->create();

        $response = $this->getJson('/api/agency/bookings?offering_id=' . $this->offering->id);

        $this->assertStandardResponse($response);
        $data = $response->json('data.data');
        $this->assertCount(2, $data);
    }

    public function test_agency_can_sort_bookings(): void
    {
        $customer = $this->createCustomerUser();

        $booking1 = Booking::factory()
            ->forTimeSlot($this->timeSlot->id)
            ->forUser($customer->id)
            ->create(['total_price' => 100]);

        $booking2 = Booking::factory()
            ->forTimeSlot($this->timeSlot->id)
            ->forUser($customer->id)
            ->create(['total_price' => 50]);

        $booking3 = Booking::factory()
            ->forTimeSlot($this->timeSlot->id)
            ->forUser($customer->id)
            ->create(['total_price' => 200]);

        $response = $this->getJson('/api/agency/bookings?sort_by=total_price&sort_direction=asc');

        $this->assertStandardResponse($response);
        $data = $response->json('data.data');
        $this->assertEquals($booking2->total_price, $data[0]['total_price']);
        $this->assertEquals($booking1->total_price, $data[1]['total_price']);
        $this->assertEquals($booking3->total_price, $data[2]['total_price']);
    }

    // ===== VIEW Tests =====

    public function test_agency_can_view_booking_details(): void
    {
        $customer = $this->createCustomerUser();
        $booking = Booking::factory()
            ->forTimeSlot($this->timeSlot->id)
            ->forUser($customer->id)
            ->create();

        $response = $this->getJson("/api/agency/bookings/{$booking->id}");

        $this->assertStandardResponse($response);
        $responseData = $response->json('data');
        $this->assertEquals($booking->id, $responseData['id']);
        $this->assertEquals($booking->booking_reference, $responseData['booking_reference']);
    }

    public function test_agency_cannot_view_another_agencys_booking(): void
    {
        $otherAgency = $this->createAgencyUser();
        $otherOffering = Offering::factory()->forUser($otherAgency->id)->create();
        $otherDay = OfferingDay::factory()->forOffering($otherOffering->id)->create();
        $otherSlot = OfferingTimeSlot::factory()
            ->forOfferingDay($otherDay->id)
            ->create(['offering_id' => $otherOffering->id]);

        $customer = $this->createCustomerUser();
        $otherBooking = Booking::factory()
            ->forTimeSlot($otherSlot->id)
            ->forUser($customer->id)
            ->create();

        $response = $this->getJson("/api/agency/bookings/{$otherBooking->id}");

        $response->assertStatus(401);
    }

    public function test_viewing_nonexistent_booking_returns_404(): void
    {
        $response = $this->getJson('/api/agency/bookings/99999');

        $response->assertStatus(404);
        $this->assertEquals('Booking not found', $response->json('errorMessage'));
    }

    // ===== CANCEL Tests =====

    public function test_agency_can_cancel_booking(): void
    {
        $customer = $this->createCustomerUser();
        $booking = Booking::factory()
            ->forTimeSlot($this->timeSlot->id)
            ->forUser($customer->id)
            ->create(['status' => 'confirmed']);

        $this->timeSlot->update(['booked_count' => 1]);

        $response = $this->deleteJson("/api/agency/bookings/{$booking->id}", [
            'cancellation_reason' => 'Offering no longer available',
        ]);

        $this->assertStandardResponse($response);
        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'status' => 'cancelled',
            'payment_status' => 'refunded',
            'cancellation_reason' => 'Offering no longer available',
        ]);

        $this->timeSlot->refresh();
        $this->assertEquals(0, $this->timeSlot->booked_count);
    }

    public function test_agency_can_cancel_without_reason(): void
    {
        $customer = $this->createCustomerUser();
        $booking = Booking::factory()
            ->forTimeSlot($this->timeSlot->id)
            ->forUser($customer->id)
            ->create(['status' => 'confirmed']);

        $response = $this->deleteJson("/api/agency/bookings/{$booking->id}");

        $this->assertStandardResponse($response);
        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'status' => 'cancelled',
        ]);
    }

    public function test_agency_cannot_cancel_already_cancelled_booking(): void
    {
        $customer = $this->createCustomerUser();
        $booking = Booking::factory()
            ->forTimeSlot($this->timeSlot->id)
            ->forUser($customer->id)
            ->cancelled()
            ->create();

        $response = $this->deleteJson("/api/agency/bookings/{$booking->id}");

        $response->assertStatus(400);
        $this->assertEquals('This booking has already been cancelled', $response->json('errorMessage'));
    }

    public function test_agency_cannot_cancel_another_agencys_booking(): void
    {
        $otherAgency = $this->createAgencyUser();
        $otherOffering = Offering::factory()->forUser($otherAgency->id)->create();
        $otherDay = OfferingDay::factory()->forOffering($otherOffering->id)->create();
        $otherSlot = OfferingTimeSlot::factory()
            ->forOfferingDay($otherDay->id)
            ->create(['offering_id' => $otherOffering->id]);

        $customer = $this->createCustomerUser();
        $otherBooking = Booking::factory()
            ->forTimeSlot($otherSlot->id)
            ->forUser($customer->id)
            ->create(['status' => 'confirmed']);

        $response = $this->deleteJson("/api/agency/bookings/{$otherBooking->id}");

        $response->assertStatus(401);
        $this->assertEquals('You are not allowed to access this page.', $response->json('errorMessage'));
    }

    // ===== NO-SHOW Tests =====

    public function test_agency_can_mark_booking_as_no_show(): void
    {
        $customer = $this->createCustomerUser();

        // Create a booking in the past
        $pastDay = OfferingDay::factory()
            ->forOffering($this->offering->id)
            ->create(['date' => now()->subDays(1)]);
        $pastSlot = OfferingTimeSlot::factory()
            ->forOfferingDay($pastDay->id)
            ->create([
                'offering_id' => $this->offering->id,
                'start_time' => '09:00',
                'end_time' => '10:00',
                'booked_count' => 1,
            ]);

        $booking = Booking::factory()
            ->forTimeSlot($pastSlot->id)
            ->forUser($customer->id)
            ->create(['status' => 'confirmed', 'payment_status' => 'paid']);

        $response = $this->postJson("/api/agency/bookings/{$booking->id}/mark-no-show");

        $this->assertStandardResponse($response);
        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'status' => 'no_show',
            'payment_status' => 'paid', // No refund for no-shows
        ]);

        // Check that capacity was freed
        $pastSlot->refresh();
        $this->assertEquals(0, $pastSlot->booked_count);
    }

    public function test_agency_cannot_mark_future_booking_as_no_show(): void
    {
        $customer = $this->createCustomerUser();

        // Create a booking in the future
        $futureDay = OfferingDay::factory()
            ->forOffering($this->offering->id)
            ->create(['date' => now()->addDays(5)]);
        $futureSlot = OfferingTimeSlot::factory()
            ->forOfferingDay($futureDay->id)
            ->create([
                'offering_id' => $this->offering->id,
                'start_time' => '14:00',
                'end_time' => '15:00',
            ]);

        $booking = Booking::factory()
            ->forTimeSlot($futureSlot->id)
            ->forUser($customer->id)
            ->create(['status' => 'confirmed']);

        $response = $this->postJson("/api/agency/bookings/{$booking->id}/mark-no-show");

        $response->assertStatus(400);
        $this->assertEquals('Cannot mark as no-show - booking time has not passed yet', $response->json('errorMessage'));
    }

    public function test_agency_cannot_mark_cancelled_booking_as_no_show(): void
    {
        $customer = $this->createCustomerUser();
        $booking = Booking::factory()
            ->forTimeSlot($this->timeSlot->id)
            ->forUser($customer->id)
            ->cancelled()
            ->create();

        $response = $this->postJson("/api/agency/bookings/{$booking->id}/mark-no-show");

        $response->assertStatus(400);
        $this->assertEquals('Cannot mark a cancelled booking as no-show', $response->json('errorMessage'));
    }

    public function test_agency_cannot_mark_another_agencys_booking_as_no_show(): void
    {
        $otherAgency = $this->createAgencyUser();
        $otherOffering = Offering::factory()->forUser($otherAgency->id)->create();
        $otherDay = OfferingDay::factory()
            ->forOffering($otherOffering->id)
            ->create(['date' => now()->subDays(1)]);
        $otherSlot = OfferingTimeSlot::factory()
            ->forOfferingDay($otherDay->id)
            ->create([
                'offering_id' => $otherOffering->id,
                'start_time' => '09:00',
                'end_time' => '10:00',
            ]);

        $customer = $this->createCustomerUser();
        $otherBooking = Booking::factory()
            ->forTimeSlot($otherSlot->id)
            ->forUser($customer->id)
            ->create(['status' => 'confirmed']);

        $response = $this->postJson("/api/agency/bookings/{$otherBooking->id}/mark-no-show");

        $response->assertStatus(401);
        $this->assertEquals('You are not allowed to access this page.', $response->json('errorMessage'));
    }

    // ===== AUTHORIZATION Tests =====

    public function test_unauthenticated_user_cannot_access_agency_booking_endpoints(): void
    {
        $this->actingAsUnauthenticated();

        $routes = [
            ['GET', '/api/agency/bookings', []],
            ['GET', '/api/agency/bookings/1', []],
            ['DELETE', '/api/agency/bookings/1', []],
            ['POST', '/api/agency/bookings/1/mark-no-show', []],
        ];

        foreach ($routes as [$method, $uri, $data]) {
            $response = $this->json($method, $uri, $data);
            $response->assertStatus(401);
            $this->assertEquals('Unauthenticated.', $response->json('message'));
        }
    }


    public function test_customer_cannot_access_agency_booking_endpoints(): void
    {
        $this->actingAsCustomer();

        $routes = [
            ['GET', '/api/agency/bookings', []],
            ['GET', '/api/agency/bookings/1', []],
            ['DELETE', '/api/agency/bookings/1', []],
            ['POST', '/api/agency/bookings/1/mark-no-show', []],
        ];

        foreach ($routes as [$method, $uri, $data]) {
            $response = $this->json($method, $uri, $data);
            $response->assertStatus(401);
            $this->assertEquals('You are not allowed to access this page.', $response->json('errorMessage'));
        }
    }
}
