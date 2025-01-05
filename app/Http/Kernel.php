<?php

namespace App\Http;

use Illuminate\Foundation\Http\Kernel as HttpKernel;

class Kernel extends HttpKernel
{
    /**
     * The application's global HTTP middleware stack.
     *
     * These middleware are run during every request to your application.
     *
     * @var array
     */
    protected $middleware = [

        // Handle post size limits
        \Illuminate\Foundation\Http\Middleware\ValidatePostSize::class,

        // Convert empty strings to null
        \Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull::class,
    ];

    /**
     * The application's route middleware groups.
     *
     * @var array
     */
    protected $middlewareGroups = [
        'api' => [
            // Middleware for stateful Sanctum requests (e.g., SPA)
            \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,

            // Throttle API requests
            'throttle:api',

            // Resolve route model bindings
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ],
    ];

    /**
     * The application's route middleware.
     *
     * These middleware may be assigned to groups or used individually.
     *
     * @var array
     */
    protected $routeMiddleware = [
        // Authenticate users
        'auth' => \App\Http\Middleware\Authenticate::class,
        // Authenticate using HTTP Basic Auth
        'auth.basic' => \Illuminate\Auth\Middleware\AuthenticateWithBasicAuth::class,
        // Handle route model bindings
        'bindings' => \Illuminate\Routing\Middleware\SubstituteBindings::class,
        // Set cache headers
        'cache.headers' => \Illuminate\Http\Middleware\SetCacheHeaders::class,
        // Authorize user actions
        'can' => \Illuminate\Auth\Middleware\Authorize::class,
        // Redirect authenticated users
        'guest' => \App\Http\Middleware\RedirectIfAuthenticated::class,
        // Require password confirmation for sensitive actions
        'password.confirm' => \Illuminate\Auth\Middleware\RequirePassword::class,
        // Validate signed routes
        'signed' => \Illuminate\Routing\Middleware\ValidateSignature::class,
        // Throttle requests
        'throttle' => \Illuminate\Routing\Middleware\ThrottleRequests::class,
        // Ensure email verification
        'verified' => \Illuminate\Auth\Middleware\EnsureEmailIsVerified::class,
    ];
}