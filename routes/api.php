<?php

use App\Http\Controllers\AuthenticationController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/ping', fn () => ['ok' => true]);

Route::post('/users/create', [UserController::class, 'create']);
Route::post('/users/register', [UserController::class, 'register']);
Route::middleware('auth:sanctum')->post('/users/authenticate', [AuthenticationController::class, 'authenticate']);

