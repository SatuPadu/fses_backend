<?php

namespace App\Modules\Auth\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use App\Modules\Auth\Services\AuthService;
use App\Modules\Auth\Requests\LoginRequest;
use Illuminate\Support\Facades\Log;
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
            
            $result = $this->authService->login([
                'identity' => $validatedData['identity'],
                'password' => $validatedData['password']
            ]);
            
            if (!$result) {
                return $this->sendUnauthorizedError('Invalid credentials.');
            }
            
            return $this->sendResponse($result, 'User logged in successfully.');
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->sendUnauthorizedError("Invalid credentials");
        } catch (\Exception $e) {
            // Check if the error message indicates a locked account
            $isAccountLocked = str_contains($e->getMessage(), 'Account is locked');
            
            return $this->sendError(
                $e->getMessage(),
                [
                    'error' => $e->getMessage(),
                    'account_locked' => $isAccountLocked
                ],
                $isAccountLocked ? 423 : 500
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
            $user = Auth::user()->load('lecturer');
            
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

    /**
     * Reactivate a locked user account
     *
     * @param int $id
     * @return JsonResponse
     */
    public function reactivateAccount(int $id): JsonResponse
    {
        try {
            $result = $this->authService->reactivateAccount($id);
            return $this->sendResponse($result, 'Account reactivated successfully.');
        } catch (\Exception $e) {
            return $this->sendError('Failed to reactivate account.', ['error' => $e->getMessage()], 400);
        }
    }
}