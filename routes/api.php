<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Auth\Controllers\AuthController;
use App\Modules\Articles\Controllers\TopicController;
use App\Modules\Articles\Controllers\ArticleController;
use App\Modules\Articles\Controllers\UserPreferencesController;



Route::get('/status', function () {
    return "up and running";
});

Route::prefix('auth')->group(function () {
    // Public routes
    Route::post('login', [AuthController::class, 'login']);
    Route::post('register', [AuthController::class, 'register']);


    // Protected routes (require authentication)
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('user', [AuthController::class, 'user']);
    });
});

Route::prefix('article')->group(function () {
    // Public Routes
    Route::get('/', [ArticleController::class, 'index']);
    Route::get('/topics', [TopicController::class, 'getTopics']);
    Route::get('sources', [ArticleController::class, 'getSources']);
    Route::get('detail/{id}', [ArticleController::class, 'show']);

});

Route::middleware('auth:sanctum')->prefix('user')->group(function () {
    Route::post('set-preferences', [UserPreferencesController::class, 'setPreferences']);
    Route::get('preferences', [UserPreferencesController::class, 'getPreferences']);
    Route::get('feed', [UserPreferencesController::class, 'getPersonalizedFeed']);
});