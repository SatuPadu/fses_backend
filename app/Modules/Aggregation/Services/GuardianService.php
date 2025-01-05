<?php

namespace App\Modules\Aggregation\Services;

use Illuminate\Support\Facades\Http;

/**
 * Class GuardianService
 *
 * Service to fetch articles from The Guardian Open Platform API.
 *
 * @package App\Modules\Aggregation\Services
 */
class GuardianService
{
    /**
     * Fetch articles from The Guardian Open Platform API for a list of topics.
     *
     * @param array $topics A list of topics to fetch articles for.
     * @return array An array of articles.
     */
    public function fetchArticles(array $topics): array
    {
        $apiKey = config('services.guardian.key'); // Retrieve API key from configuration
        $baseUrl = 'https://content.guardianapis.com/search';
        $articles = [];
        
        try {
            foreach ($topics as $topic) {
                // Make a request for each topic
                $response = Http::get($baseUrl, [
                    'api-key'      => $apiKey,
                    'q'            => $topic, // Use the topic in the search query
                    'show-fields'  => 'headline,trailText,body,byline,firstPublicationDate,thumbnail',
                    'page-size'    => 50,
                    'order-by'     => 'newest',
                    'show-tags'    => 'all',
                ]);
                if ($response->status() === 429) {
                    // Log and break out of the loop on rate limit
                    logger()->error('Rate limit reached for The Guardian API. Breaking out of the loop.', [
                        'topic' => $topic,
                    ]);
                    break;
                }

                if ($response->successful()) {
                    $data = $response->json();
                    $results = $data['response']['results'] ?? [];

                    foreach ($results as $item) {
                        $fields = $item['fields'] ?? [];

                        $articles[] = [
                            'title'        => $fields['headline'] ?? $item['webTitle'] ?? '',
                            'description'  => $fields['trailText'] ?? null,
                            'content'      => $fields['body'] ?? null,
                            'author'       => $fields['byline'] ?? null,
                            'source_name'  => 'The Guardian',
                            'url'          => $item['webUrl'] ?? null,
                            'published_at' => $fields['firstPublicationDate'] ?? ($item['webPublicationDate'] ?? now()),
                            'thumbnail'    => $fields['thumbnail'] ?? null,
                            'topic'        => $topic, // Attach the topic
                        ];
                    }
                } else {
                    logger()->error('GuardianService Error: HTTP ' . $response->status() . ' - ' . $response->body(), [
                        'topic' => $topic,
                    ]);
                }
            }
        } catch (\Exception $e) {
            logger()->error('GuardianService Exception: ' . $e->getMessage());
        }

        return $articles;
    }
}