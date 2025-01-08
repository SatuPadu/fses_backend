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
     * @return void
     */
    public function savePreferences(int $userId, array $data): void
    {
        if (!empty($data['topics'])) {
            $this->savePreferencesByType($userId, 'topics', $data['topics']);
        }

        if (!empty($data['sources'])) {
            $this->savePreferencesByType($userId, 'sources', $data['sources']);
        }

        if (!empty($data['authors'])) {
            $this->savePreferencesByType($userId, 'authors', $data['authors']);
        }
    }

    /**
     * Save preferences by type.
     *
     * @param int $userId
     * @param string $type
     * @param array $values
     * @return void
     */
    protected function savePreferencesByType(int $userId, string $type, array $values): void
    {
        UserPreference::where('user_id', $userId)
            ->where('type', $type)
            ->delete();

        $preferences = array_map(function ($value) use ($userId, $type) {
            return [
                'user_id' => $userId,
                'type' => $type,
                'value' => $value,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }, $values);

        UserPreference::insert($preferences);
    }

    /**
     * Get all user preferences.
     *
     * @param int $userId
     * @return array
     */
    public function getPreferences(int $userId): array
    {
        return UserPreference::where('user_id', $userId)
            ->get(['type', 'value'])
            ->groupBy('type')
            ->map(fn($items) => $items->pluck('value')->toArray())
            ->toArray();
    }

    /**
     * Get all valid topics from the database.
     *
     * @return array
     */
    public function getValidTopics(): array
    {
        return Article::distinct()->pluck('topic')->toArray();
    }

    /**
     * Get all valid sources from the database.
     *
     * @return array
     */
    public function getValidSources(): array
    {
        return Article::distinct()->pluck('source_name')->toArray();
    }

    /**
     * Get all valid authors from the database.
     *
     * @return array
     */
    public function getValidAuthors(): array
    {
        return Article::distinct()->pluck('author')->toArray();
    }
}