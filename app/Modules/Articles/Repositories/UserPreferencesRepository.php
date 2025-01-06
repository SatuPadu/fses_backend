<?php

namespace App\Modules\Articles\Repositories;

use App\Modules\Articles\Models\Article;
use App\Modules\Articles\Models\UserPreference;

class UserPreferencesRepository
{
    /**
     * Save user preferences.
     *
     * @param int $userId
     * @param array $data
     * @return array
     */
    public function savePreferences(int $userId, array $data): array
    {
        $preferences = UserPreference::updateOrCreate(
            ['user_id' => $userId],
            [
                'topics' => $data['topics'] ?? [],
                'sources' => $data['sources'] ?? [],
                'categories' => $data['categories'] ?? [],
            ]
        );

        return $preferences->toArray();
    }

    /**
     * Retrieve user preferences.
     *
     * @param int $userId
     * @return array|null
     */
    public function getPreferences(int $userId): ?array
    {
        $preferences = UserPreference::where('user_id', $userId)->first();
        return $preferences ? $preferences->toArray() : null;
    }

    /**
     * Retrieve articles based on user preferences.
     *
     * @param array $preferences
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getArticlesByPreferences(array $preferences)
    {
        $query = Article::query();

        if (!empty($preferences['topics'])) {
            $query->whereIn('topic', $preferences['topics']);
        }

        if (!empty($preferences['sources'])) {
            $query->whereIn('source_name', $preferences['sources']);
        }

        if (!empty($preferences['categories'])) {
            $query->whereIn('category', $preferences['categories']);
        }

        $perPage = $filters['per_page'] ?? 10;
        return $query->paginate($perPage);
    }
}