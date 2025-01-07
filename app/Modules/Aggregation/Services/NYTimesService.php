<?php

namespace App\Modules\Aggregation\Services;

use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

class NYTimesService
{
    protected string $apiUrl = 'https://api.nytimes.com/svc/search/v2/articlesearch.json';

    /**
     * Fetch articles from the New York Times Article Search API for a list of topics.
     *
     * @param array $topics A list of topics to fetch articles for.
     * @return array An array of articles mapped to your schema.
     * @throws \RuntimeException If a rate limit (429) or other issues occur.
     */
    public function fetchArticles(array $topics): array
    {
        $apiKey = config('services.nytimes.key');
        if (!$apiKey) {
            throw new \RuntimeException('NYTimes API key is missing in the configuration.');
        }

        $articles = [];

        foreach ($topics as $topic) {
            try {
                $response = Http::timeout(10) // Add a timeout for the request
                    ->get($this->apiUrl, [
                        'api-key' => $apiKey,
                        'sort'    => 'newest',
                        'q'       => $topic,
                        'fl'      => 'web_url,snippet,pub_date,headline,byline,news_desk,section_name,lead_paragraph,multimedia',
                    ]);

                if ($response->status() === 429) {
                    throw new \RuntimeException("Rate limit reached for NYTimes API while processing topic '{$topic}'.");
                }

                if (!$response->successful()) {
                    throw new \RuntimeException("NYTimes API returned HTTP {$response->status()} for topic '{$topic}': " . $response->body());
                }

                $docs = $response->json('response.docs') ?? [];
                $articles = array_merge($articles, $this->mapArticles($docs, $topic));

            } catch (\RuntimeException $e) {
                throw $e; // Rethrow to handle specific cases in calling code
            } catch (\Exception $e) {
                throw new \RuntimeException("Unexpected error occurred while fetching articles for topic '{$topic}': " . $e->getMessage());
            }
        }

        return $articles;
    }

    /**
     * Map API response to `articles` table schema.
     *
     * @param array $docs API response documents.
     * @param string $topic The topic being processed.
     * @return array Mapped articles.
     */
    protected function mapArticles(array $docs, string $topic): array
    {
        return array_map(function (array $doc) use ($topic): array {
            return [
                'title'        => $this->extractHeadline($doc),
                'description'  => $doc['snippet'] ?? null,
                'content'      => $doc['lead_paragraph'] ?? null,
                'author'       => $this->extractAuthor($doc),
                'source_name'  => 'New York Times',
                'url'          => $doc['web_url'] ?? null,
                'thumbnail'    => $this->extractThumbnail($doc),
                'published_at' => $this->parseDate($doc['pub_date'] ?? null),
                'topic'        => $topic,
            ];
        }, $docs);
    }

    /**
     * Extract the headline.
     *
     * @param array $doc API response document.
     * @return string
     */
    protected function extractHeadline(array $doc): string
    {
        return $doc['headline']['main'] ?? 'Untitled';
    }

    /**
     * Extract the author.
     *
     * @param array $doc API response document.
     * @return string|null
     */
    protected function extractAuthor(array $doc): ?string
    {
        if (!empty($doc['byline']['original'])) {
            return $doc['byline']['original'];
        }

        if (!empty($doc['byline']['person']) && is_array($doc['byline']['person'])) {
            $authors = array_map(function (array $person): string {
                return trim("{$person['firstname']} {$person['middlename']} {$person['lastname']}");
            }, $doc['byline']['person']);

            return implode(', ', array_filter($authors));
        }

        return null;
    }

    /**
     * Extract the thumbnail URL from the multimedia array.
     *
     * @param array $doc API response document.
     * @return string|null
     */
    protected function extractThumbnail(array $doc): ?string
    {
        if (!empty($doc['multimedia']) && is_array($doc['multimedia'])) {
            foreach ($doc['multimedia'] as $media) {
                if ($media['type'] === 'image') {
                    return 'https://www.nytimes.com/' . ($media['url'] ?? '');
                }
            }
        }
        return null;
    }

    /**
     * Parse the publication date into MySQL-compatible datetime format.
     *
     * @param string|null $dateString
     * @return string
     */
    protected function parseDate(?string $dateString): string
    {
        if (!$dateString) {
            return now()->format('Y-m-d H:i:s');
        }

        try {
            return Carbon::parse($dateString)->format('Y-m-d H:i:s');
        } catch (\Exception $e) {
            return now()->format('Y-m-d H:i:s');
        }
    }
}