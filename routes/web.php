<?php

use App\Http\Controllers\EmailVerificationController;
use App\Http\Controllers\PasswordResetController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/email/verify/{user}', [EmailVerificationController::class, 'verify'])
    ->name('email.verify');

Route::get('/password/reset/{user}', [PasswordResetController::class, 'show'])
    ->name('password.reset.form');
Route::post('/password/reset/{user}', [PasswordResetController::class, 'reset'])
    ->name('password.reset.submit');
