<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Auth\Controllers\AuthController;


Route::get('/', function () {
    return "up and running";
});
// Public routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', [AuthController::class, 'user']);
    Route::post('/logout', [AuthController::class, 'logout']);
});