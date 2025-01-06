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
     */
    public function setPreferences(int $userId, array $data)
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
}