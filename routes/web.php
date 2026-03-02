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

Route::get('/otp/copy/{code}', fn (string $code) => view('otp-copy', ['code' => $code]))
    ->name('otp.copy');

Route::get('/robots.txt', fn () => response(view('robots'), 200, ['Content-Type' => 'text/plain; charset=UTF-8']))
    ->name('robots');

Route::get('/sitemap.xml', fn () => response(view('sitemap'), 200, ['Content-Type' => 'application/xml; charset=UTF-8']))
    ->name('sitemap');
