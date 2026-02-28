<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\InvitationController;
use App\Http\Controllers\OrganizationController;
use App\Http\Controllers\OrganizationMemberController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\TicketController;
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
    Route::get('organizations/{organization}/invitations', [InvitationController::class, 'index']);
    Route::post('organizations/{organization}/invitations', [InvitationController::class, 'store']);
    Route::delete('organizations/{organization}/invitations/{invitation}', [InvitationController::class, 'destroy']);
    Route::get('organizations/{organization}/projects', [ProjectController::class, 'index']);
    Route::post('organizations/{organization}/projects', [ProjectController::class, 'store']);
    Route::get('organizations/{organization}/projects/{project}', [ProjectController::class, 'show']);
    Route::patch('organizations/{organization}/projects/{project}', [ProjectController::class, 'update']);
    Route::delete('organizations/{organization}/projects/{project}', [ProjectController::class, 'destroy']);
    Route::get('organizations/{organization}/projects/{project}/tickets', [TicketController::class, 'index']);
    Route::post('organizations/{organization}/projects/{project}/tickets', [TicketController::class, 'store']);
    Route::get('organizations/{organization}/projects/{project}/tickets/{ticket}', [TicketController::class, 'show']);
    Route::patch('organizations/{organization}/projects/{project}/tickets/{ticket}', [TicketController::class, 'update']);
    Route::delete('organizations/{organization}/projects/{project}/tickets/{ticket}', [TicketController::class, 'destroy']);
    Route::get('organizations/{organization}/projects/{project}/tickets/{ticket}/comments', [CommentController::class, 'index']);
    Route::post('organizations/{organization}/projects/{project}/tickets/{ticket}/comments', [CommentController::class, 'store']);
    Route::get('organizations/{organization}/projects/{project}/tickets/{ticket}/comments/{comment}', [CommentController::class, 'show']);
    Route::patch('organizations/{organization}/projects/{project}/tickets/{ticket}/comments/{comment}', [CommentController::class, 'update']);
    Route::delete('organizations/{organization}/projects/{project}/tickets/{ticket}/comments/{comment}', [CommentController::class, 'destroy']);
    Route::get('organizations/{organization}/members', [OrganizationMemberController::class, 'index']);
    Route::get('organizations/{organization}/members/me', [OrganizationMemberController::class, 'me']);
    Route::post('organizations/{organization}/members', [OrganizationMemberController::class, 'store']);
    Route::patch('organizations/{organization}/members/{user}', [OrganizationMemberController::class, 'update']);
    Route::delete('organizations/{organization}/members/{user}', [OrganizationMemberController::class, 'destroy']);
    Route::post('invitations/accept', [InvitationController::class, 'accept']);
    Route::apiResource('sessions', UserSessionController::class)->only([
        'index',
        'store',
        'show',
        'destroy',
    ]);
});
