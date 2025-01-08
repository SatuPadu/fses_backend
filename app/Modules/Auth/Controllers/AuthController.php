<?php

namespace App\Modules\Auth\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use App\Modules\Auth\Services\AuthService;
use App\Modules\Auth\Requests\LoginRequest;
use App\Modules\Auth\Requests\RegisterRequest;

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
    }

    /**
     * @OA\Post(
     *     path="/auth/register",
     *     tags={"Authentication"},
     *     summary="Register a new user",
     *     description="Register a user by providing their details.",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="name", type="string", example="John Doe"),
     *             @OA\Property(property="email", type="string", example="john.doe@user.com"),
     *             @OA\Property(property="password", type="string", example="password123"),
     *             @OA\Property(property="password_confirmation", type="string", example="password123")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="User registered successfully.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="User registered successfully."),
     *             @OA\Property(property="data", type="object", @OA\Property(property="user", type="object"))
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Validation error."),
     *             @OA\Property(property="data", type="object", @OA\Property(property="errors", type="object"))
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Unexpected error.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="An unexpected error occurred. Please try again later."),
     *             @OA\Property(property="data", type="object", @OA\Property(property="error", type="string"))
     *         )
     *     )
     * )
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
     * @OA\Post(
     *     path="/auth/login",
     *     tags={"Authentication"},
     *     summary="Login a user",
     *     description="Authenticate a user with their email and password.",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="email", type="string", example="john.doe@user.com"),
     *             @OA\Property(property="password", type="string", example="password123")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="User logged in successfully.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="User logged in successfully."),
     *             @OA\Property(property="data", type="object", @OA\Property(property="token", type="string"))
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Unexpected error.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="An unexpected error occurred. Please try again later."),
     *             @OA\Property(property="data", type="object", @OA\Property(property="error", type="string"))
     *         )
     *     )
     * )
     */
    public function login(Request $request): JsonResponse
    {
        try {
            $validatedData = LoginRequest::validate($request);
            $result = $this->authService->login($validatedData);

            if (!$result) {
                return $this->sendUnauthorizedError('Invalid credentials.');
            }

            return $this->sendResponse($result, 'User logged in successfully.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->sendUnauthorizedError("Invalid credentials");
        } catch (\Exception $e) {
            return $this->sendError(
                'An unexpected error occurred. Please try again later.',
                ['error' => $e->getMessage()],
                500
            );
        }
    }

    /**
    * @OA\Post(
    *     path="/auth/logout",
    *     tags={"Authentication"},
    *     summary="Logout user",
    *     description="Logs out the currently authenticated user.",
    *     security={{"sanctum": {}}},
    *     @OA\Response(
    *         response=200,
    *         description="User logged out successfully.",
    *         @OA\JsonContent(
    *             type="object",
    *             @OA\Property(property="success", type="boolean", example=true),
    *             @OA\Property(property="message", type="string", example="User logged out successfully."),
    *             @OA\Property(property="data", type="null")
    *         )
    *     ),
    *     @OA\Response(
    *         response=401,
    *         description="Unauthorized access",
    *         @OA\JsonContent(
    *             type="object",
    *             @OA\Property(property="success", type="boolean", example=false),
    *             @OA\Property(property="message", type="string", example="Unauthorized."),
    *             @OA\Property(property="data", type="null")
    *         )
    *     ),
    *     @OA\Response(
    *         response=500,
    *         description="Unexpected error.",
    *         @OA\JsonContent(
    *             type="object",
    *             @OA\Property(property="success", type="boolean", example=false),
    *             @OA\Property(property="message", type="string", example="Logout failed. Please try again later."),
    *             @OA\Property(property="data", type="object", @OA\Property(property="error", type="string"))
    *         )
    *     )
    * )
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
     * @OA\Get(
     *     path="/auth/user",
     *     tags={"Authentication"},
     *     summary="Get authenticated user",
     *     description="Retrieve the details of the currently authenticated user.",
     *     security={{"sanctum": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="User retrieved successfully.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="User retrieved successfully."),
     *             @OA\Property(property="data", type="object", @OA\Property(property="user", type="object"))
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="User not authenticated."),
     *             @OA\Property(property="data", type="null")
     *         )
     *     )
     * )
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