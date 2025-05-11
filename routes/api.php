<?php

use App\Modules\UserManagement\Controllers\UserManagementController;
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
        // Routes that are accessible even if password not updated
        Route::post('set-new-password', [PasswordResetController::class, 'setNewPassword']);
        Route::post('logout', [AuthController::class, 'logout']);
        
        // Routes that require password to be updated
        Route::middleware('password.updated')->group(function () {
            Route::get('auth-user', [AuthController::class, 'user']);
            Route::post('refresh', [AuthController::class, 'refresh']);
            // Add other protected routes here
        });
    });
});

// Password Reset Routes
Route::prefix('password')->group(function () {
    Route::post('reset-link', [PasswordResetController::class, 'sendResetLink'])
        ->middleware('throttle:5,1');

    Route::post('reset', [PasswordResetController::class, 'resetPassword'])
        ->middleware('throttle:5,1');
});

// User Management Routes
Route::get('/lecturers', [UserManagementController::class, 'index']);
Route::post('/lecturers', [UserManagementController::class, 'store']);
Route::put('/lecturers/{id}', [UserManagementController::class, 'update']);
Route::delete('/lecturers/{id}', [UserManagementController::class, 'destroy']);
