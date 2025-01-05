<?php

namespace App\Modules\Articles\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Modules\Articles\Models\Article;
use App\Modules\Articles\Models\UserPreference;

class UserPreferencesController extends Controller
{
    /**
     * Save user preferred topics, sources, and categories.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function setTopics(Request $request)
    {
        $validated = $request->validate([
            'topics' => 'nullable|array',
        ]);

        $user = Auth::user();

        // Update or create user preferences
        $preferences = UserPreference::updateOrCreate(
            ['user_id' => $user->id],
            [
                'topics' => $validated['topics'] ?? [],
                'sources' => $validated['sources'] ?? [],
                'categories' => $validated['categories'] ?? [],
            ]
        );

        return response()->json([
            'message' => 'Preferences updated successfully.',
            'preferences' => $preferences,
        ], 200);
    }

    /**
     * Retrieve user preferred topics, sources, and categories.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getPreferences()
    {
        $user = Auth::user();
        $preferences = UserPreference::where('user_id', $user->id)->first();

        if (!$preferences) {
            return response()->json([
                'message' => 'No preferences found for the user.',
                'preferences' => null,
            ], 200);
        }

        return response()->json([
            'message' => 'User preferences retrieved successfully.',
            'preferences' => $preferences,
        ], 200);
    }

    /**
     * Retrieve a personalized feed based on user preferences.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getPersonalizedFeed()
    {
        $user = Auth::user();
        $preferences = UserPreference::where('user_id', $user->id)->first();

        if (!$preferences) {
            return response()->json([
                'message' => 'No preferences found for the user.',
                'feed' => [],
            ], 200);
        }

        $query = Article::query();

        if (!empty($preferences->topics)) {
            $query->whereIn('topic', $preferences->topics);
        }

        if (!empty($preferences->sources)) {
            $query->whereIn('source', $preferences->sources);
        }

        if (!empty($preferences->categories)) {
            $query->whereIn('category', $preferences->categories);
        }

        $articles = $query->paginate(10);
        return $this->sendResponse($articles, 'Personalized feed retrieved successfully.');
    }
}