<?php

namespace App\Modules\Articles\Services;

use App\Modules\Articles\Repositories\TopicRepository;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

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
     */
    private function fetchTopicsFromApi(): array
    {
        try {
            $response = Http::get($this->apiUrl, ['api-key' => $this->apiKey]);

            if ($response->successful()) {
                return $response->json('response.results') ?? [];
            }

            Log::error('Failed to fetch topics from The Guardian API', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return [];
        } catch (\Exception $e) {
            Log::error('Exception occurred while fetching topics', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [];
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
            ->reject(function ($topicName) {
                return str_contains($topicName, 'Guardian') 
                    || str_contains($topicName, 'Observer') 
                    || $topicName === 'Wellness (Do NOT use)';
            })
            ->unique()
            ->map(fn($topicName) => ['name' => $topicName])
            ->toArray();
    }

    /**
     * Fetch topics from the API, process them, and store them in the database.
     *
     * @return void
     */
    public function fetchAndStoreTopics(): void
    {
        $sections = $this->fetchTopicsFromApi();
        $processedTopics = $this->processTopics($sections);

        if (!empty($processedTopics)) {
            $this->topicRepo->insertTopics($processedTopics);
            Log::info('Topics successfully fetched, processed, and stored.', [
                'count' => count($processedTopics),
            ]);
        } else {
            Log::warning('No valid topics to store after processing.');
        }
    }

    /**
     * Retrieve all topic names from the repository.
     *
     * @return array
     */
    public function getTopics(): array
    {
        try {
            return $this->topicRepo->getAllTopicNames();
        } catch (\Exception $e) {
            Log::error('Failed to retrieve topics from the repository.', [
                'error' => $e->getMessage(),
            ]);

            throw new \RuntimeException('Unable to fetch topics. Please try again later.');
        }
    }
}