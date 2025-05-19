<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Auth\Controllers\AuthController;
use App\Modules\Auth\Controllers\PasswordResetController;
use App\Modules\UserManagement\Controllers\UserController;
use App\Modules\UserManagement\Controllers\LecturerController;

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

// User Management Routes - Lecturers
Route::prefix('lecturers')->group(function () {
    Route::get('/', [LecturerController::class, 'index']);
    Route::post('/', [LecturerController::class, 'store']);
    Route::put('/{id}', [LecturerController::class, 'update']);
    Route::delete('/{id}', [LecturerController::class, 'destroy']);
});

// User Management Routes - Users
Route::prefix('users')->group(function () {
    Route::get('/', [UserController::class, 'index']);
    Route::post('/', [UserController::class, 'store']);
    Route::put('/{id}', [UserController::class, 'update']);
    Route::delete('/{id}', [UserController::class, 'destroy']);
});
