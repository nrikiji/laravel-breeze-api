<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\VerifyEmailController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\EmailVerificationNotificationController;
use Illuminate\Support\Facades\Route;

Route::post('/register', [RegisteredUserController::class, 'store']);

Route::post('/login', [AuthenticatedSessionController::class, 'store']);

Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
    ->middleware('auth:sanctum');

Route::post('/email/verification-notification', [EmailVerificationNotificationController::class, 'store'])
    ->middleware(['auth:sanctum', 'throttle:6,1']);

Route::post('/forgot-password', [PasswordResetLinkController::class, 'store']);

Route::post('/reset-password', [NewPasswordController::class, 'store'])
    ->name('password.update');

Route::get('/verify-email/{id}/{hash}', [VerifyEmailController::class, '__invoke'])
    //->middleware(['auth', 'signed', 'throttle:6,1'])
    ->middleware(['auth:sanctum', 'signed'])
    ->name('verification.verify');
