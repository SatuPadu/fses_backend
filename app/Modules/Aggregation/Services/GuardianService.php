<?php

namespace App\Modules\Aggregation\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Client\RequestException;

/**
 * Class GuardianService
 *
 * Service to fetch articles from The Guardian Open Platform API.
 */
class GuardianService
{
    private const BASE_URL = 'https://content.guardianapis.com/search';
    private const PAGE_SIZE = 50;

    /**
     * Fetch articles from The Guardian Open Platform API for a list of topics.
     *
     * @param array $topics A list of topics to fetch articles for.
     * @return array An array of articles.
     * @throws \RuntimeException
     */
    public function fetchArticles(array $topics): array
    {
        $apiKey = config('services.guardian.key');
        if (!$apiKey) {
            throw new \RuntimeException('Guardian API key is missing in the configuration.');
        }

        $articles = [];

        foreach ($topics as $topic) {
            try {
                $response = Http::timeout(10) // Set a timeout to avoid indefinite waits
                    ->get(self::BASE_URL, [
                        'api-key'      => $apiKey,
                        'q'            => $topic,
                        'show-fields'  => 'headline,trailText,body,byline,firstPublicationDate,thumbnail',
                        'page-size'    => self::PAGE_SIZE,
                        'order-by'     => 'newest',
                        'show-tags'    => 'all',
                    ]);

                if ($response->status() === 429) {
                    throw new \RuntimeException('Rate limit reached for The Guardian API. Please retry later.');
                }

                if (!$response->successful()) {
                    throw new RequestException($response);
                }

                $data = $response->json();
                $results = $data['response']['results'] ?? [];

                $articles = array_merge($articles, $this->transformArticles($results, $topic));
            } catch (RequestException $e) {
                throw new \RuntimeException(
                    "Failed to fetch articles for topic '{$topic}'. HTTP Error: " . $e->getMessage()
                );
            } catch (\Exception $e) {
                throw new \RuntimeException(
                    "An unexpected error occurred while fetching articles for topic '{$topic}': " . $e->getMessage()
                );
            }
        }

        return $articles;
    }

    /**
     * Transform API results into structured articles.
     *
     * @param array $results The API response results.
     * @param string $topic The topic associated with the articles.
     * @return array The transformed articles.
     */
    private function transformArticles(array $results, string $topic): array
    {
        return collect($results)->map(function ($item) use ($topic) {
            $fields = $item['fields'] ?? [];
            return [
                'title'        => $fields['headline'] ?? $item['webTitle'] ?? '',
                'description'  => $fields['trailText'] ?? null,
                'content'      => $fields['body'] ?? null,
                'author'       => $fields['byline'] ?? null,
                'source_name'  => 'The Guardian',
                'url'          => $item['webUrl'] ?? null,
                'published_at' => $fields['firstPublicationDate'] ?? ($item['webPublicationDate'] ?? now()),
                'thumbnail'    => $fields['thumbnail'] ?? null,
                'topic'        => $topic,
            ];
        })->toArray();
    }
}