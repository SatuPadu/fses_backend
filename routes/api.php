<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Auth\Controllers\AuthController;
use App\Modules\Auth\Controllers\PasswordResetController;

// API Health Check
Route::get('/status', function () {
    return response()->json([
        'status' => 'up',
    ]);
});

// Authentication Routes
Route::prefix('auth')->group(function () {
    // Public routes
    Route::post('login', [AuthController::class, 'login']);

    // Protected routes (require JWT authentication)
    Route::middleware('jwt.verify')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('auth-user', [AuthController::class, 'user']);
        Route::post('refresh', [AuthController::class, 'refresh']);
    });
});

// Password Reset Routes
Route::prefix('password')->group(function () {
    Route::post('reset-link', [PasswordResetController::class, 'sendResetLink'])
        ->middleware('throttle:5,1');

    Route::post('reset', [PasswordResetController::class, 'resetPassword'])
        ->middleware('throttle:5,1');
});