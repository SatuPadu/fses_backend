<?php

namespace App\Modules\Articles\Services;

use App\Modules\Articles\Repositories\UserPreferencesRepository;

class UserPreferencesService
{
    protected UserPreferencesRepository $preferencesRepo;

    /**
     * Inject the UserPreferencesRepository dependency.
     */
    public function __construct(UserPreferencesRepository $preferencesRepo)
    {
        $this->preferencesRepo = $preferencesRepo;
    }

    /**
     * Save user preferences.
     *
     * @param int $userId
     * @param array $data
     * @return array
     */
    public function setPreferences(int $userId, array $data): array
    {
        return $this->preferencesRepo->savePreferences($userId, $data);
    }

    /**
     * Retrieve user preferences.
     *
     * @param int $userId
     * @return array|null
     */
    public function getPreferences(int $userId): ?array
    {
        return $this->preferencesRepo->getPreferences($userId);
    }

    /**
     * Retrieve a personalized feed based on user preferences.
     *
     * @param int $userId
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getPersonalizedFeed(int $userId): array
    {
        $preferences = $this->preferencesRepo->getPreferences($userId);

        if (!$preferences) {
            return [];
        }

        return $this->preferencesRepo->getArticlesByPreferences($preferences);
    }
}