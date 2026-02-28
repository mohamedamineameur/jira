<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\OrganizationController;
use App\Http\Controllers\OrganizationMemberController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UserSessionController;
use Illuminate\Support\Facades\Route;

Route::post('users', [UserController::class, 'store']);
Route::post('login', [AuthController::class, 'login']);

Route::middleware('auth.api')->group(function (): void {
    Route::post('logout', [AuthController::class, 'logout']);
    Route::get('me', [UserController::class, 'me']);
    Route::apiResource('users', UserController::class)->only([
        'index',
        'show',
        'destroy',
    ]);
    Route::patch('users/{user}/profile', [UserController::class, 'updateProfile']);
    Route::patch('users/{user}/password', [UserController::class, 'updatePassword']);
    Route::patch('users/{user}/admin', [UserController::class, 'updateByAdmin'])->middleware('admin');

    Route::apiResource('admins', AdminController::class)->middleware('admin');
    Route::apiResource('organizations', OrganizationController::class);
    Route::patch('organizations/{organization}/plan', [OrganizationController::class, 'updatePlan']);
    Route::get('organizations/{organization}/members', [OrganizationMemberController::class, 'index']);
    Route::get('organizations/{organization}/members/me', [OrganizationMemberController::class, 'me']);
    Route::post('organizations/{organization}/members', [OrganizationMemberController::class, 'store']);
    Route::patch('organizations/{organization}/members/{user}', [OrganizationMemberController::class, 'update']);
    Route::delete('organizations/{organization}/members/{user}', [OrganizationMemberController::class, 'destroy']);
    Route::apiResource('sessions', UserSessionController::class)->only([
        'index',
        'store',
        'show',
        'destroy',
    ]);
});
