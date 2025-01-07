<?php

namespace App\Modules\Auth\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Modules\Auth\Services\PasswordResetService;
use App\Modules\Auth\Requests\ForgotPasswordRequest;
use App\Modules\Auth\Requests\ResetPasswordRequest;
use Illuminate\Http\JsonResponse;
use Exception;

class PasswordResetController extends Controller
{
    private PasswordResetService $passwordResetService;

    public function __construct(PasswordResetService $passwordResetService)
    {
        $this->passwordResetService = $passwordResetService;
    }

    /**
     * Handle forgot password requests.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function forgotPassword(Request $request): JsonResponse
    {
        try {
            $validatedData = ForgotPasswordRequest::validate($request);
            $token = $this->passwordResetService->sendResetLink($validatedData['email']);

            return $this->sendResponse($token, 'Password reset link sent.');
        } catch (Exception $e) {
            return $this->sendError(
                'Failed to send password reset link.',
                ['error' => $e->getMessage()],
                JsonResponse::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Handle password reset requests.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function resetPassword(Request $request): JsonResponse
    {
        try {
            $validatedData = ResetPasswordRequest::validate($request);
            $this->passwordResetService->resetPassword($validatedData);

            return $this->sendResponse(null, 'Password reset successful.');
        } catch (Exception $e) {
            return $this->sendError(
                'Failed to reset password.',
                ['error' => $e->getMessage()],
                JsonResponse::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }
}