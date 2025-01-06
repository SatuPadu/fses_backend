<?php

namespace App\Modules\Articles\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use App\Modules\Articles\Services\UserPreferencesService;

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

    /**
     * Save user preferred topics, sources, and categories.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function setPreferences(Request $request)
    {
        try {
            $userId = Auth::id();
            $validatedData = $request->validate([
                'topics' => 'nullable|array',
                'sources' => 'nullable|array',
                'categories' => 'nullable|array',
            ]);

            $preferences = $this->preferencesService->setPreferences($userId, $validatedData);

            return $this->sendResponse($preferences, 'Preferences updated successfully.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->sendValidationError($e->errors());
        } catch (\Exception $e) {
            return $this->sendError(
                'Failed to save preferences. Please try again later.',
                ['error' => $e->getMessage()],
                500
            );
        }
    }

    /**
     * Retrieve user preferred topics, sources, and categories.
     *
     * @return JsonResponse
     */
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

    /**
     * Retrieve a personalized feed based on user preferences.
     *
     * @return JsonResponse
     */
    public function getPersonalizedFeed()
    {
        try {
            $userId = Auth::id();
            $feed = $this->preferencesService->getPersonalizedFeed($userId);

            return $this->sendResponse($feed, 'Personalized feed retrieved successfully.');
        } catch (\Exception $e) {
            return $this->sendError(
                'Failed to retrieve personalized feed. Please try again later.',
                ['error' => $e->getMessage()],
                500
            );
        }
    }
}