<?php

namespace App\Modules\Auth\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use App\Modules\Auth\Services\AuthService;
use App\Modules\Auth\Requests\LoginRequest;
use Tymon\JWTAuth\Exceptions\JWTException;

/**
 * @OA\Tag(
 *     name="Authentication",
 *     description="API Endpoints related to User Authentication"
 * )
 */
class AuthController extends Controller
{
    private AuthService $authService;

    /**
     * Inject the AuthService dependency.
     */
    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
        $this->middleware('jwt.verify', ['except' => ['login']]);
    }

    public function login(Request $request): JsonResponse
    {
        try {
            $validatedData = LoginRequest::validate($request);
            
            $identity = $validatedData['identity'];
            
            // Check if the identity is an email
            $isEmail = filter_var($identity, FILTER_VALIDATE_EMAIL);
            $field = $isEmail ? 'email' : 'staff_number';
            
            // Add proper quotes for non-numeric values in logs
            $credentials = [
                $field => $identity,
                'password' => $validatedData['password']
            ];
            
            if (!$token = Auth::attempt($credentials)) {
                return $this->sendUnauthorizedError('Invalid credentials.');
            }
            
            // Update last login timestamp
            $user = Auth::user();
            $this->authService->updateLastLogin($user);
            
            return $this->sendResponse([
                'access_token' => $token,
                'token_type' => 'bearer',
                'expires_in' => Auth::factory()->getTTL() * 60,
                'user' => $user
            ], 'User logged in successfully.');
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->sendUnauthorizedError("Invalid credentials");
        } catch (\Exception $e) {
            return $this->sendError(
                'Database connection error. Please try again later.',
                ['error' => $e->getMessage()],
                500
            );
        }
    }


    public function logout(): JsonResponse
    {
        try {
            Auth::logout();
            return $this->sendResponse(null, 'User logged out successfully.');
        } catch (\Exception $e) {
            return $this->sendError('Logout failed. Please try again later.', ['error' => $e->getMessage()], 500);
        }
    }

    public function user(): JsonResponse
    {
        try {
            $user = Auth::user();
            
            // Load any relationships if needed
            // $user->load('roles');
            
            return $this->sendResponse(["user" => $user], 'User retrieved successfully.');
        } catch (\Exception $e) {
            return $this->sendError('Failed to retrieve user.', ['error' => $e->getMessage()], 500);
        }
    }
    
    public function refresh(): JsonResponse
    {
        try {
            $token = Auth::refresh();
            
            return $this->sendResponse([
                'access_token' => $token,
                'token_type' => 'bearer',
                'expires_in' => Auth::factory()->getTTL() * 60
            ], 'Token refreshed successfully.');
        } catch (JWTException $e) {
            return $this->sendUnauthorizedError('Token could not be refreshed.');
        } catch (\Exception $e) {
            return $this->sendError('An unexpected error occurred.', ['error' => $e->getMessage()], 500);
        }
    }
}