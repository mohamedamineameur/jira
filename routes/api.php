<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UserSessionController;
use Illuminate\Support\Facades\Route;

Route::apiResource('admins', AdminController::class)->middleware('admin');
Route::apiResource('users', UserController::class)->except(['update']);
Route::patch('users/{user}/profile', [UserController::class, 'updateProfile']);
Route::patch('users/{user}/password', [UserController::class, 'updatePassword']);
Route::patch('users/{user}/admin', [UserController::class, 'updateByAdmin'])->middleware('admin');
Route::apiResource('sessions', UserSessionController::class)->only([
    'index',
    'store',
    'show',
    'destroy',
]);
