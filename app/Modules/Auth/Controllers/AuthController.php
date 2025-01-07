<?php

namespace App\Modules\Auth\Controllers;


use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use App\Modules\Auth\Services\AuthService;
use App\Modules\Auth\Requests\LoginRequest;
use App\Modules\Auth\Requests\RegisterRequest;


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
             // Validate the request data using RegisterRequest
             $validatedData = RegisterRequest::validate($request);
     
             // Pass the validated data to the AuthService for user registration
             $result = $this->authService->register($validatedData);
     
             // Return a success response with the registration result
             return $this->sendResponse($result, 'User registered successfully.');
         } catch (\Illuminate\Validation\ValidationException $e) {
             // Handle validation errors and return a structured error response
             return $this->sendValidationError($e->errors());
         } catch (\Exception $e) {
             // Handle unexpected errors and log them for debugging     
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
            // Retrieve validated data from the LoginRequest
            $validatedData = LoginRequest::validate($request);
    
            // Pass the validated data to the AuthService for authentication
            $result = $this->authService->login($validatedData);
    
            if (!$result) {
                return $this->sendUnauthorizedError('Invalid credentials.');
            }
    
            // Return a success response with the login result
            return $this->sendResponse($result, 'User logged in successfully.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Handle validation errors and return a structured error response
            return $this->sendValidationError($e->errors());
        } catch (\Exception $e) {
            // Handle unexpected errors and return a generic error response
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