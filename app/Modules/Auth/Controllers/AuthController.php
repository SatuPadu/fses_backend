<?php

namespace App\Modules\Auth\Controllers;


use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use App\Modules\Auth\Services\AuthService;
use App\Modules\Auth\Requests\LoginRequest;
use App\Modules\Auth\Requests\RegisterRequest;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    private AuthService $authService;

    /**
     * Inject the AuthService dependency.
     */
    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    /**
     * Handle user registration.
     *
     * @param Request $request
     * @return JsonResponse
     */

     public function register(Request $request): JsonResponse
     {
         try {
             $validatedData = RegisterRequest::validate($request);
             $result = $this->authService->register($validatedData);
     
             return $this->sendCreatedResponse($result, 'User registered successfully.');
         } catch (\Illuminate\Validation\ValidationException $e) {
             return $e->getResponse() ?: $this->sendValidationError($e->errors());
         } catch (\Exception $e) {
             return $this->sendError(
                 'An unexpected error occurred. Please try again later.',
                 ['error' => $e->getMessage()],
                 500
             );
         }
     }

    /**
     * Handle user login.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function login(Request $request): JsonResponse
    {
        try {
            // Validate the login request data
            $validatedData = LoginRequest::validate($request);

            // Attempt to authenticate the user
            $result = $this->authService->login($validatedData);

            // If authentication fails, return an unauthorized error
            if (!$result) {
                return $this->sendUnauthorizedError('Invalid credentials.');
            }

            // If successful, return a success response
            return $this->sendResponse($result, 'User logged in successfully.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Return unauthorized response for validation errors
            return $this->sendUnauthorizedError("Invalid credentials");
        } catch (\Exception $e) {
            // Handle unexpected exceptions and log the error
            return $this->sendError(
                'An unexpected error occurred. Please try again later.',
                ['error' => $e->getMessage()],
                500
            );
        }
    }

 /**
     * Handle user logout.
     *
     * @param Request $request 
     * @return JsonResponse
     * 
     */
    public function logout(Request $request): JsonResponse
    {

        try {
            $request->user()->tokens()->delete();
            return $this->sendResponse(null, 'User logged out successfully.');
        } catch (\Exception $e) {
            return $this->sendError('Logout failed. Please try again later.', ['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get the authenticated user's details.
     *
     * @return JsonResponse
     */
    public function user(): JsonResponse
    {
        $user = Auth::user();

        if (!$user) {
            return $this->sendUnauthorizedError('User not authenticated.');
        }

        return $this->sendResponse(["user" => $user], 'User retrieved successfully.');
    }
}