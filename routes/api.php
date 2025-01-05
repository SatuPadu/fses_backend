<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Auth\Controllers\AuthController;



Route::get('/', function () {
    return "up and running";
});

Route::prefix('auth')->group(function () {
    // Public routes
    Route::post('login', [AuthController::class, 'login']);
    Route::post('register', [AuthController::class, 'register']);


    // Protected routes (require authentication)
    Route::middleware()->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('user', [AuthController::class, 'user']);
    });
});