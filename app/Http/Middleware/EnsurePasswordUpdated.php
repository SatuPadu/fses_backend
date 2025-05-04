<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsurePasswordUpdated
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Get authenticated user
        $user = Auth::user();
        
        // Check if user exists and password hasn't been updated
        if ($user && !$user->is_password_updated) {
            // Allow access to set-new-password endpoint
            if ($request->is('api/auth/set-new-password')) {
                return $next($request);
            }
            
            // Block access to all other protected routes
            return response()->json([
                'success' => false,
                'message' => 'You must change your password before continuing',
                'code' => 'PASSWORD_CHANGE_REQUIRED'
            ], Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}