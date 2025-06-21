<?php

namespace App\Modules\Aggregation\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\RequestException;

class NewsApiService
{
    protected string $apiUrl = 'https://newsapi.org/v2/everything';

    /**
     * Fetch articles for specified topics.
     *
     * @param  array  $topics  List of topics to fetch articles for.
     * @return array
     * @throws \RuntimeException If rate limit or other errors occur.
     */
    public function fetchArticles(array $topics): array
    {
        $apiKey = config('services.newsapi.key');
        if (!$apiKey) {
            throw new \RuntimeException('NewsAPI key is missing in the configuration.');
        }

        $articles = [];

        foreach ($topics as $topic) {
            try {
                $response = Http::timeout(10)
                    ->get($this->apiUrl, [
                        'apiKey'    => $apiKey,
                        'qInTitle'  => $topic,
                        'from'      => now()->subDays(30)->toDateString(),
                        'sortBy'    => 'popularity',
                    ]);

                if ($response->status() === 429) {
                    throw new \RuntimeException('Rate limit reached for NewsAPI. Please retry later.');
                }

                if (!$response->successful()) {
                    throw new RequestException($response);
                }

                $articles = array_merge($articles, $this->transformArticles($response->json('articles') ?? [], $topic));
            } catch (RequestException $e) {
                throw new \RuntimeException(
                    "Failed to fetch articles for topic '{$topic}': " . $e->getMessage()
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
     * @param  array  $articles  The API response articles.
     * @param  string $topic     The topic associated with the articles.
     * @return array
     */
    protected function transformArticles(array $articles, string $topic): array
    {
        return collect($articles)->map(function (array $article) use ($topic) {
            return [
                'title'        => $article['title'] ?? '',
                'description'  => $article['description'] ?? '',
                'content'      => $article['content'] ?? '',
                'author'       => $article['author'] ?? '',
                'source_name'  => $article['source']['name'] ?? '',
                'published_at' => $article['publishedAt'] ?? now(),
                'url'          => $article['url'] ?? null,
                'thumbnail'    => $this->extractThumbnail($article),
                'topic'        => $topic,
            ];
        })->toArray();
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