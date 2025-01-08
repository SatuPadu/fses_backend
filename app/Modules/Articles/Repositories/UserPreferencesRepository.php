<?php

namespace App\Modules\Articles\Repositories;

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
        // Process topics
        if (!empty($data['topics'])) {
            $this->savePreferencesByType($userId, 'topics', $data['topics']);
        }

        // Process sources
        if (!empty($data['sources'])) {
            $this->savePreferencesByType($userId, 'sources', $data['sources']);
        }

        // Process authors
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
        // Remove old preferences of this type
        UserPreference::where('user_id', $userId)
            ->where('type', $type)
            ->delete();

        // Insert new preferences
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
     * Get user preferences by type.
     *
     * @param int $userId
     * @param string $type
     * @return array
     */
    public function getPreferencesByType(int $userId, string $type): array
    {
        return UserPreference::where('user_id', $userId)
            ->where('type', $type)
            ->pluck('value')
            ->toArray();
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
            ->map(function ($items) {
                return $items->pluck('value')->toArray();
            })
            ->toArray();
    }
}