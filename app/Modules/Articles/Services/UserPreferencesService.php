<?php

namespace App\Modules\Articles\Services;

use Illuminate\Support\Facades\Cache;
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
     * @return void
     */
    public function setPreferences(int $userId, array $data)
    {
        // Save preferences in the repository
        $this->preferencesRepo->savePreferences($userId, $data);

        // Invalidate the cache for the user
        Cache::forget("user_preferences_{$userId}");
    }

    /**
     * Retrieve user preferences.
     *
     * @param int $userId
     * @return array|null
     */
    public function getPreferences(int $userId): ?array
    {
        // Attempt to get preferences from cache
        return Cache::remember("user_preferences_{$userId}", now()->addMinutes(30), function () use ($userId) {
            return $this->preferencesRepo->getPreferences($userId);
        });
    }
}