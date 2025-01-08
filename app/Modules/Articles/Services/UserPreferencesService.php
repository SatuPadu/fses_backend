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
 * Validate and save user preferences.
 *
 * @param int $userId
 * @param array $data
 * @return void
 *
 * @throws \InvalidArgumentException
 */
public function setPreferences(int $userId, array $data): void
{
    try {
        // Validate preferences
        $this->validatePreferences($data);

        $this->preferencesRepo->savePreferences($userId, $data);

        Cache::forget("user_preferences_{$userId}");
    } catch (\InvalidArgumentException $e) {
        throw new \InvalidArgumentException($e->getMessage());
    } catch (\Exception $e) {
        throw new \Exception("An unexpected error occurred while saving preferences.");
    }
}

    /**
     * Retrieve user preferences.
     *
     * @param int $userId
     * @return array|null
     */
    public function getPreferences(int $userId): ?array
    {
        return Cache::remember("user_preferences_{$userId}", now()->addMinutes(30), function () use ($userId) {
            return $this->preferencesRepo->getPreferences($userId);
        });
    }

    /**
     * Validate preferences before saving.
     *
     * @param array $data
     * @return void
     *
     * @throws \InvalidArgumentException
     */
    protected function validatePreferences(array $data): void
    {
        $validTopics = $this->preferencesRepo->getValidTopics();
        $validSources = $this->preferencesRepo->getValidSources();
        $validAuthors = $this->preferencesRepo->getValidAuthors();

        if (!empty($data['topics'])) {
            foreach ($data['topics'] as $topic) {
                if (!in_array($topic, $validTopics)) {
                    throw new \InvalidArgumentException("Invalid topic: {$topic}");
                }
            }
        }

        if (!empty($data['sources'])) {
            foreach ($data['sources'] as $source) {
                if (!in_array($source, $validSources)) {
                    throw new \InvalidArgumentException("Invalid source: {$source}");
                }
            }
        }

        if (!empty($data['authors'])) {
            foreach ($data['authors'] as $author) {
                if (!in_array($author, $validAuthors)) {
                    throw new \InvalidArgumentException("Invalid author: {$author}");
                }
            }
        }
    }
}