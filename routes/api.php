<?php

use App\Http\Controllers\UserController;
use App\Http\Controllers\UserSessionController;
use Illuminate\Support\Facades\Route;

Route::apiResource('users', UserController::class);
Route::apiResource('sessions', UserSessionController::class)->only([
    'index',
    'store',
    'show',
    'destroy',
]);
