<?php

namespace App\Modules\Aggregation\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class NewsApiService
{
    protected string $apiUrl = 'https://newsapi.org/v2/everything';

    /**
     * Fetch articles for specified topics.
     *
     * @param  array  $topics  List of topics to fetch articles for.
     * @return array
     * @throws \Exception If a rate limit (429) is reached.
     */
    public function fetchArticles(array $topics): array
    {
        $apiKey = config('services.newsapi.key'); // Retrieve API key from configuration

        try {
            $articles = collect();

            foreach ($topics as $topic) {
                // Fetch articles for each topic
                $response = Http::get($this->apiUrl, [
                    'apiKey' => $apiKey,
                    'qInTitle' => urlencode($topic),
                    'from' => now()->subDays(30)->toDateString(), // Fetch articles from the last 30 days
                    'sortBy' => 'popularity',
                ]);

                if ($response->status() === 429) {
                    // Log and break out of the loop on rate limit
                    Log::error('Rate limit reached for NewsAPI. Breaking out of the loop.', [
                        'topic' => $topic,
                    ]);
                    break;
                }

                if ($response->successful()) {
                    $articles = $articles->merge(
                        collect($response->json('articles'))->map(function (array $article) use ($topic): array {
                            return [
                                'title' => $article['title'] ?? '',
                                'description' => $article['description'] ?? '',
                                'content' => $article['content'] ?? '',
                                'author' => $article['author'] ?? '',
                                'source_name' => $article['source']['name'] ?? '',
                                'published_at' => $article['publishedAt'] ?? now(),
                                'url' => $article['url'] ?? null,
                                'thumbnail' => $this->extractThumbnail($article),
                                'topic' => $topic,
                            ];
                        })
                    );
                } else {
                    Log::error('Failed to fetch articles from NewsAPI', [
                        'status' => $response->status(),
                        'body' => $response->body(),
                        'topic' => $topic,
                    ]);
                }
            }

            return $articles->toArray();
        } catch (\Exception $e) {
            Log::error('Exception occurred while fetching articles from NewsAPI', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e; // Re-throw the exception to ensure it propagates
        }
    }

    /**
     * Extract the thumbnail URL from the article.
     *
     * @param  array  $article  The article data.
     * @return string|null
     */
    protected function extractThumbnail(array $article): ?string
    {
        return $article['urlToImage'] ?? null;
    }
}