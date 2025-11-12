<?php

use App\Constants\UserRoles;
use App\Http\Controllers\Agency\OfferingController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Middleware\AdminArea;
use App\Http\Middleware\AgencyArea;
use App\Http\Middleware\CustomerArea;
use Illuminate\Support\Facades\Route;

Route::get('/ping', fn() => ['ok' => true]);

Route::post('/auth/login', [AuthController::class, 'login']);

Route::group(['middleware' => ['auth:sanctum']], function () {
    Route::get('/me', [AuthController::class, 'me']);

    Route::group(['middleware' => CustomerArea::class, 'prefix' => UserRoles::CUSTOMER], function () {
        Route::get('/test-customer-area', [AuthController::class, 'testCustomerArea']);
    });

    Route::group(['middleware' => AgencyArea::class, 'prefix' => UserRoles::AGENCY], function () {
        Route::get('/offerings', [OfferingController::class, 'index']);
        Route::post('/offerings', [OfferingController::class, 'create']);
        Route::put('/offerings/{offering}', [OfferingController::class, 'update'])->can('update', 'offering');
        Route::delete('/offerings/{offering}', [OfferingController::class, 'delete'])->can('delete', 'offering');
    });

    Route::group(['middleware' => AdminArea::class, 'prefix' => UserRoles::ADMIN], function () {
        Route::post('/register', [AuthController::class, 'register']);
    });
});



