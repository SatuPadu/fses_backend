<?php

namespace App\Modules\Aggregation\Services;

use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

class NYTimesService
{
    /**
     * Fetch articles from the New York Times Article Search API for a list of topics.
     *
     * @param array $topics A list of topics to fetch articles for.
     * @return array An array of articles mapped to your schema.
     * @throws \Exception If a rate limit (429) is reached.
     */
    public function fetchArticles(array $topics): array
    {
        $apiKey = config('services.nytimes.key'); // Store the API key in config or .env
        $baseUrl = 'https://api.nytimes.com/svc/search/v2/articlesearch.json';
        $articles = [];

        try {
            foreach ($topics as $topic) {
                // Query parameters for each topic
                $query = [
                    'api-key' => $apiKey,
                    'sort' => 'newest', // Default sort order
                    'q' => $topic, // Use the topic in the search query
                    'fl' => 'web_url,snippet,pub_date,headline,byline,news_desk,section_name,lead_paragraph,multimedia',
                ];

                $response = Http::get($baseUrl, $query);

                if ($response->status() === 429) {
                    // Log and break out of the loop on rate limit
                    logger()->error("NYTimesService Rate Limit Reached: HTTP 429 for topic {$topic}");
                    break;
                }

                if ($response->successful()) {
                    $data = $response->json();
                    $docs = $data['response']['docs'] ?? [];
                    $mappedDocs = $this->mapArticles($docs, $topic);
                    $articles = array_merge($articles, $mappedDocs);
                } else {
                    logger()->error("NYTimesService Error: HTTP {$response->status()} - {$response->body()} for topic {$topic}");
                }
            }
        } catch (\Exception $e) {
            logger()->error("NYTimesService Exception: " . $e->getMessage());
            throw $e; // Re-throw the exception for further handling
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
                'title'        => $this->extractHeadline($doc),       // Maps to `title`
                'description'  => $doc['snippet'] ?? null,           // Maps to `description`
                'content'      => $doc['lead_paragraph'] ?? null,    // Maps to `content`
                'author'       => $this->extractAuthor($doc),        // Maps to `author`
                'source_name'  => 'New York Times',                  // Maps to `source_name`
                'url'          => $doc['web_url'] ?? null,           // Maps to `url`
                'thumbnail'    => $this->extractThumbnail($doc),     // Maps to `thumbnail`
                'published_at' => $this->parseDate($doc['pub_date'] ?? null), // Maps to `published_at`
                'topic'        => $topic,                            // Attach the topic
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
            logger()->warning("Invalid pub_date format: {$dateString}");
            return now()->format('Y-m-d H:i:s');
        }
    }
}