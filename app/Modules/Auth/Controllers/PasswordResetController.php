<?php

namespace App\Modules\Auth\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Modules\Auth\Services\PasswordResetService;
use App\Modules\Auth\Requests\ForgotPasswordRequest;
use App\Modules\Auth\Requests\ResetPasswordRequest;
use Illuminate\Http\JsonResponse;
use Exception;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * @OA\Tag(
 *     name="Password Reset",
 *     description="API Endpoints related to Password Reset functionality"
 * )
 */
class PasswordResetController extends Controller
{
    private PasswordResetService $passwordResetService;

    public function __construct(PasswordResetService $passwordResetService)
    {
        $this->passwordResetService = $passwordResetService;
    }

    /**
     * @OA\Post(
     *     path="/password/reset-link",
     *     tags={"Password Reset"},
     *     summary="Send password reset link",
     *     description="Send a password reset link to the user's email.",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="email", type="string", example="user@user.com")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Password reset link sent.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Password reset link sent."),
     *             @OA\Property(property="data", type="object", @OA\Property(property="token", type="string"))
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Invalid email address.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Invalid email address."),
     *             @OA\Property(property="data", type="object", @OA\Property(property="error", type="string"))
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Unexpected error.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="An unexpected error occurred."),
     *             @OA\Property(property="data", type="object", @OA\Property(property="error", type="string"))
     *         )
     *     )
     * )
     */
    public function sendResetLink(Request $request): JsonResponse
    {
        try {
            $validatedData = ForgotPasswordRequest::validate($request);
            $token = $this->passwordResetService->sendResetLink($validatedData['email']);

            return $this->sendResponse($token, 'Password reset link sent.');
        } catch (Exception $e) {
            return $this->sendError(
                'Invalid email address.',
                ['error' => $e->getMessage()],
                JsonResponse::HTTP_NOT_FOUND
            );
        }
    }

    /**
     * @OA\Post(
     *     path="/password/reset",
     *     tags={"Password Reset"},
     *     summary="Reset password",
     *     description="Reset the user's password using the reset token.",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="token", type="string", example="reset_token"),
     *             @OA\Property(property="email", type="string", example="user@user.com"),
     *             @OA\Property(property="password", type="string", example="new_password"),
     *             @OA\Property(property="password_confirmation", type="string", example="new_password")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Password reset successful.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Password reset successful."),
     *             @OA\Property(property="data", type="null")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Invalid token or email.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Invalid token or email."),
     *             @OA\Property(property="data", type="object", @OA\Property(property="error", type="string"))
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Unexpected error.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="An unexpected error occurred."),
     *             @OA\Property(property="data", type="object", @OA\Property(property="error", type="string"))
     *         )
     *     )
     * )
     */
    public function resetPassword(Request $request): JsonResponse
    {
        try {
            $validatedData = ResetPasswordRequest::validate($request);
            $this->passwordResetService->resetPassword($validatedData);

            return $this->sendResponse(null, 'Password reset successful.');
        } catch (HttpException $e) {
            return $this->sendError(
                $e->getMessage(),
                [],
                $e->getStatusCode()
            );
        } catch (\Exception $e) {
            return $this->sendError(
                'An unexpected error occurred.',
                ['error' => $e->getMessage()],
                500
            );
        }
    }
}