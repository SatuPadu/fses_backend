<?php

namespace App\Modules\Articles\Services;

use App\Modules\Articles\Repositories\TopicRepository;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class TopicService
{
    private string $apiUrl;
    private string $apiKey;
    private TopicRepository $topicRepo;

    public function __construct(TopicRepository $topicRepo)
    {
        $this->apiUrl = 'https://content.guardianapis.com/sections';
        $this->apiKey = config('services.guardian.key');
        $this->topicRepo = $topicRepo;
    }

    /**
     * Fetch topics from The Guardian API.
     *
     * @return array
     * @throws \RuntimeException
     */
    private function fetchTopicsFromApi(): array
    {
        try {
            $response = Http::get($this->apiUrl, ['api-key' => $this->apiKey]);

            if ($response->successful()) {
                return $response->json('response.results') ?? [];
            }

            throw new \RuntimeException('Failed to fetch topics from The Guardian API. Response status: ' . $response->status());
        } catch (\Exception $e) {
            throw new \RuntimeException('An error occurred while fetching topics from The Guardian API: ' . $e->getMessage());
        }
    }

    /**
     * Process and filter the topics.
     *
     * @param array $sections
     * @return array
     */
    private function processTopics(array $sections): array
    {
        return collect($sections)
            ->pluck('webTitle')
            ->filter()
            ->reject(fn($topicName) => 
                str_contains($topicName, 'Guardian') ||
                str_contains($topicName, 'Observer') ||
                $topicName === 'Wellness (Do NOT use)'
            )
            ->unique()
            ->map(fn($topicName) => ['name' => $topicName])
            ->toArray();
    }

    /**
     * Fetch topics from the API, process them, and store them in the database.
     *
     * @return void
     * @throws \RuntimeException
     */
    public function fetchAndStoreTopics(): void
    {
        $sections = Cache::remember('topics:raw', now()->addMinutes(30), function () {
            return $this->fetchTopicsFromApi();
        });

        $processedTopics = $this->processTopics($sections);

        if (!empty($processedTopics)) {
            $this->topicRepo->insertTopics($processedTopics);
        } else {
            throw new \RuntimeException('No valid topics available for storing.');
        }
    }

    /**
     * Retrieve all topic names from the repository, with caching.
     *
     * @return array
     * @throws \RuntimeException
     */
    public function getTopics(): array
    {
        try {
            return Cache::remember('topics:all', now()->addMinutes(60), function () {
                return $this->topicRepo->getAllTopicNames();
            });
        } catch (\Exception $e) {
            throw new \RuntimeException('Unable to fetch topics from the repository: ' . $e->getMessage());
        }
    }
}