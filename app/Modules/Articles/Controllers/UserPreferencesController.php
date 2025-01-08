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

    /**
     * @OA\Post(
     *     path="/preferences/set-preferences",
     *     tags={"User Preferences"},
     *     summary="Set user preferences",
     *     description="Save user preferences for topics, sources, and authors.",
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="topics", type="array", @OA\Items(type="string"), example={"Technology", "Health"}),
     *             @OA\Property(property="sources", type="array", @OA\Items(type="string"), example={"Source 1", "Source 2"}),
     *             @OA\Property(property="authors", type="array", @OA\Items(type="string"), example={"Author A", "Author B"})
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Preferences updated successfully.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Preferences updated successfully.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Invalid preferences provided."),
     *             @OA\Property(property="data", type="object", @OA\Property(property="error", type="string"))
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Unexpected error.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Failed to save preferences due to an unexpected error. Please try again later."),
     *             @OA\Property(property="data", type="object", @OA\Property(property="error", type="string"))
     *         )
     *     )
     * )
     */
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

    /**
     * @OA\Get(
     *     path="/preferences",
     *     tags={"User Preferences"},
     *     summary="Get user preferences",
     *     description="Retrieve user preferences for topics, sources, and authors.",
     *     security={{"sanctum": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Preferences retrieved successfully.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Preferences retrieved successfully."),
     *             @OA\Property(property="data", type="object", additionalProperties=@OA\Property(type="array", @OA\Items(type="string")))
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="No preferences found.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="No preferences found for the user."),
     *             @OA\Property(property="data", type="null")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Unexpected error.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Failed to retrieve preferences. Please try again later."),
     *             @OA\Property(property="data", type="object", @OA\Property(property="error", type="string"))
     *         )
     *     )
     * )
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
}