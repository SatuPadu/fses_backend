<?php

namespace App\Http\Middleware;

use Closure;
use Exception;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Tymon\JWTAuth\Exceptions\JWTException;

class JWTMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        try {
            // Attempt to authenticate the user
            if (!$user = JWTAuth::parseToken()->authenticate()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'User not found'
                ], 404);
            }

            // Check if user profile is active
            if (!$user->is_active) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'User account is deactivated',
                    'error_type' => 'account_deactivated'
                ], 403);
            }

        } catch (TokenExpiredException $e) {
            // Token has expired
            return response()->json([
                'status' => 'error',
                'message' => 'Token has expired',
                'error_type' => 'token_expired'
            ], 401);
        } catch (TokenInvalidException $e) {
            // Token is invalid
            return response()->json([
                'status' => 'error',
                'message' => 'Token is invalid',
                'error_type' => 'token_invalid'
            ], 401);
        } catch (JWTException $e) {
            // General JWT authentication error
            return response()->json([
                'status' => 'error',
                'message' => 'Authorization token error',
                'error_type' => 'token_error',
                'details' => $e->getMessage()
            ], 401);
        } catch (Exception $e) {
            // Catch any other unexpected errors
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized',
                'error_type' => 'unauthorized',
                'details' => $e->getMessage()
            ], 401);
        }

        // If we've made it this far, the token is valid and user is active
        return $next($request);
    }
}