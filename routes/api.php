<?php

use Gigabyte\Identity\Http\Controllers\Api\AuthController;
use Gigabyte\Identity\Http\Middleware\EnsureTokenIsActive;
use Illuminate\Support\Facades\Route;

Route::prefix('identity')->name('identity.')->group(function () {
    Route::post('auth/login', [AuthController::class, 'login'])
        ->middleware('throttle:3,1')
        ->name('auth.login');

    Route::post('auth/verify', [AuthController::class, 'verify'])
        ->middleware('throttle:5,1')
        ->name('auth.verify');

    Route::post('auth/demo-login', [AuthController::class, 'demoLogin'])
        ->middleware('throttle:5,1')
        ->name('auth.demo-login');

    Route::middleware(['auth:sanctum', EnsureTokenIsActive::class])->group(function () {
        Route::get('auth/check', [AuthController::class, 'check'])->name('auth.check');
    });
});
