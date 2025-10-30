<?php

use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/ping', fn () => ['ok' => true]);

Route::post('/users', [UserController::class, 'create']);
Route::post('/register', [UserController::class, 'register']);
