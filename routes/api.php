<?php

use App\Constants\UserRoles;
use App\Http\Controllers\Agency\BookingController as AgencyBookingController;
use App\Http\Controllers\Agency\MediaController;
use App\Http\Controllers\Agency\OfferingController;
use App\Http\Controllers\Agency\OfferingDayController;
use App\Http\Controllers\Agency\OfferingTimeSlotController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Customer\BookingController as CustomerBookingController;
use App\Http\Controllers\Customer\OfferingController as CustomerOfferingController;
use App\Http\Controllers\HealthController;
use App\Http\Middleware\AdminArea;
use App\Http\Middleware\AgencyArea;
use App\Http\Middleware\CustomerArea;
use Illuminate\Support\Facades\Route;

Route::get('/ping', [HealthController::class, 'ping']);

Route::get('/health', [HealthController::class, 'healthCheck']);

Route::post('/auth/login', [AuthController::class, 'login']);

Route::group(['middleware' => ['auth:sanctum']], function () {
    Route::get('/me', [AuthController::class, 'me']);

    Route::group(['middleware' => CustomerArea::class, 'prefix' => UserRoles::CUSTOMER], function () {
        Route::get('/test-customer-area', [AuthController::class, 'testCustomerArea']);
        Route::get('/offerings', [CustomerOfferingController::class, 'index']);
        Route::get('/offerings/{id}', [CustomerOfferingController::class, 'show']);

        // Bookings
        Route::post('/bookings/reserve', [CustomerBookingController::class, 'reserve']);
        Route::post('/bookings/confirm-payment', [CustomerBookingController::class, 'confirmPayment']);
        Route::get('/bookings', [CustomerBookingController::class, 'index']);
        Route::get('/bookings/{id}', [CustomerBookingController::class, 'show']);
        Route::delete('/bookings/{id}', [CustomerBookingController::class, 'cancel']);
    });

    Route::group(['middleware' => AgencyArea::class, 'prefix' => UserRoles::AGENCY], function () {
        //TODO: add caching mechanism for list
        Route::get('/offerings', [OfferingController::class, 'index']);
        Route::post('/offerings', [OfferingController::class, 'create']);
        Route::put('/offerings/{id}', [OfferingController::class, 'update']);
        Route::delete('/offerings/{id}', [OfferingController::class, 'delete']);

        Route::post('/medias', [MediaController::class, 'upload']);
        Route::get('/medias/validate/{uuid}', [MediaController::class, 'validate']);
        Route::get('/medias/{uuid}', [MediaController::class, 'delete']);

        // Offering Days
        Route::post('/offering-days', [OfferingDayController::class, 'create']);
        Route::get('/offering-days', [OfferingDayController::class, 'index']);
        Route::put('/offering-days/{id}', [OfferingDayController::class, 'update']);
        Route::delete('/offering-days/{id}', [OfferingDayController::class, 'delete']);

        // Time Slots
        Route::post('/time-slots', [OfferingTimeSlotController::class, 'create']);
        Route::post('/time-slots/bulk', [OfferingTimeSlotController::class, 'bulkCreate']);
        Route::get('/time-slots', [OfferingTimeSlotController::class, 'index']);
        Route::put('/time-slots/{id}', [OfferingTimeSlotController::class, 'update']);
        Route::delete('/time-slots/{id}', [OfferingTimeSlotController::class, 'delete']);

        // Bookings
        Route::get('/bookings', [AgencyBookingController::class, 'index']);
        Route::get('/bookings/{id}', [AgencyBookingController::class, 'show']);
        Route::delete('/bookings/{id}', [AgencyBookingController::class, 'cancel']);
        Route::post('/bookings/{id}/mark-no-show', [AgencyBookingController::class, 'markNoShow']);
    });

    Route::group(['middleware' => AdminArea::class, 'prefix' => UserRoles::ADMIN], function () {
        Route::post('/register', [AuthController::class, 'register']);
    });
});
