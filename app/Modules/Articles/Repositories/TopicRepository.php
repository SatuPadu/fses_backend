<?php

namespace App\Modules\Articles\Repositories;

use App\Modules\Articles\Models\Topic;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TopicRepository
{
    /**
     * Fetch topics from The Guardian API and store unique ones.
     */
    public function fetchAndStoreTopics()
    {
        $apiKey = config('services.guardian.key'); // Get the API key from config
        $url = 'https://content.guardianapis.com/sections';

        try {
            $response = Http::get($url, ['api-key' => $apiKey]);

            if ($response->successful()) {
                $sections = $response->json('response.results') ?? [];

                // Extract and filter unique topic names
                $topics = collect($sections)
                    ->pluck('webTitle') // Extract topic names
                    ->filter() // Remove null or empty values
                    ->reject(function ($topicName) {
                        return str_contains($topicName, 'Guardian') 
                            || str_contains($topicName, 'Observer') 
                            || $topicName === 'Wellness (Do NOT use)';
                    })
                    ->unique() // Ensure uniqueness
                    ->map(function ($topicName) {
                        return ['name' => $topicName];
                    })
                    ->toArray();

                // Insert all topics in a single query
                if (!empty($topics)) {
                    Topic::insert($topics);
                }

                Log::info('Topics fetched and stored successfully.', [
                    'inserted_topics_count' => count($topics),
                ]);
            } else {
                Log::error('Failed to fetch topics from The Guardian API', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Exception occurred while fetching topics', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}