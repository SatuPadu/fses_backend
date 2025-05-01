<?php

namespace App\Modules\Articles\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use Illuminate\Validation\ValidationException;
use App\Modules\Articles\Requests\UserPreferenceRequest;
use App\Modules\Articles\Services\UserPreferencesService;

/**
 * @OA\Tag(
 *     name="User Preferences",
 *     description="API Endpoints related to User Preferences"
 * )
 */
class UserPreferencesController extends Controller
{
    protected UserPreferencesService $preferencesService;

    /**
     * Inject the UserPreferencesService dependency.
     */
    public function __construct(UserPreferencesService $preferencesService)
    {
        $this->preferencesService = $preferencesService;
    }

    public function setPreferences(Request $request)
    {
        try {
            $validatedData = UserPreferenceRequest::validate($request);

            $userId = Auth::id();
            $this->preferencesService->setPreferences($userId, $validatedData);

            return $this->sendResponse(null, 'Preferences updated successfully.');
        } catch (ValidationException $e) {
            return $this->sendValidationError($e->errors());
        } catch (\InvalidArgumentException $e) {
            return $this->sendError(
                'Invalid preferences provided.',
                ['error' => $e->getMessage()],
                422
            );
        } catch (\Exception $e) {
            return $this->sendError(
                'Failed to save preferences due to an unexpected error. Please try again later.',
                ['error' => $e->getMessage()],
                500
            );
        }
    }

    public function getPreferences()
    {
        try {
            $userId = Auth::id();
            $preferences = $this->preferencesService->getPreferences($userId);

            if (!$preferences) {
                return $this->sendResponse(null, 'No preferences found for the user.');
            }

            return $this->sendResponse($preferences, 'Preferences retrieved successfully.');
        } catch (\Exception $e) {
            return $this->sendError(
                'Failed to retrieve preferences. Please try again later.',
                ['error' => $e->getMessage()],
                500
            );
        }
    }
}