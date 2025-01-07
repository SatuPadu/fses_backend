<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Auth\Controllers\AuthController;
use App\Modules\Articles\Controllers\TopicController;
use App\Modules\Articles\Controllers\ArticleController;
use App\Modules\Auth\Controllers\PasswordResetController;
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

Route::prefix('password')->group(function () {
    Route::post('reset-link', [PasswordResetController::class, 'forgotPassword'])
        ->middleware('throttle:5,1');

    Route::post('reset', [PasswordResetController::class, 'resetPassword'])
        ->middleware('throttle:5,1');
});

Route::prefix('article')->group(function () {
    // Public Routes
    Route::get('/', [ArticleController::class, 'index']);
    Route::get('/topics', [TopicController::class, 'getTopics']);
    Route::get('detail/{id}', [ArticleController::class, 'show']);

});

Route::middleware('auth:sanctum')->prefix('preferences')->group(function () {
    Route::get('/', [UserPreferencesController::class, 'getPreferences']);
    Route::post('set-preferences', [UserPreferencesController::class, 'setPreferences']);
    Route::get('sources', [ArticleController::class, 'getSourcesByTopics']);
    Route::get('authors', [ArticleController::class, 'getAuthorsByTopicsAndSources']);
});