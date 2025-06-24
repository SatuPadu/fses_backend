<?php

namespace App\Modules\Auth\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Modules\Auth\Services\PasswordResetService;
use App\Modules\Auth\Requests\ForgotPasswordRequest;
use App\Modules\Auth\Requests\ResetPasswordRequest;
use App\Modules\Auth\Requests\SetNewPasswordRequest;
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

    public function sendResetLink(Request $request): JsonResponse
    {
        try {
            $validatedData = ForgotPasswordRequest::validate($request);
            $this->passwordResetService->sendResetLink($validatedData['email']);

            return $this->sendResponse([], 'Password reset link sent.');
        } catch (Exception $e) {
            return $this->sendError(
                'Invalid email address.',
                ['error' => $e->getMessage()],
                JsonResponse::HTTP_NOT_FOUND
            );
        }
    }

    public function resetPassword(Request $request): JsonResponse
    {
        try {
            $validatedData = ResetPasswordRequest::validate($request);
            $this->passwordResetService->resetPassword($validatedData);

            return $this->sendResponse([], 'Password reset successful.');
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

    /**
     * Set a new password for the authenticated user.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function setNewPassword(Request $request): JsonResponse
    {
        try {
            // Validate the request
            $validatedData = SetNewPasswordRequest::validate($request);
            
            // Get the authenticated user
            $user = Auth::user();
            
            // Use the password reset service to set the new password
            $result = $this->passwordResetService->setNewPassword($user, $validatedData['password']);
            
            return $this->sendResponse($result, 'Password updated successfully.');
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->sendUnauthorizedError($e->getMessage());
        } catch (\Exception $e) {
            return $this->sendError('Failed to set new password.', ['error' => $e->getMessage()], 500);
        }
    }
}