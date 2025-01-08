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