<?php

namespace App\Modules\Articles\Repositories;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use App\Modules\Articles\Models\Article;

class ArticleRepository
{
    /**
     * Fetch filtered articles with caching.
     *
     * @param array $filters
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    public function getFilteredArticles(array $filters)
    {
        $isAuthenticated = Auth::check();
        $userId = $isAuthenticated ? Auth::id() : 'guest';

        // Generate a cache key based on the user status and filters
        $cacheKey = $this->generateCacheKey($filters, $userId);

        return Cache::remember($cacheKey, now()->addMinutes(10), function () use ($filters) {
            return $this->queryFilteredArticles($filters);
        });
    }

    /**
     * Generate a unique cache key based on filters and user ID.
     *
     * @param array $filters
     * @param string|int $userId
     * @return string
     */
    private function generateCacheKey(array $filters, $userId): string
    {
        ksort($filters); // Ensure consistent filter order
        $filtersHash = md5(json_encode($filters)); // Hash filters for uniqueness
        return "articles_{$userId}_{$filtersHash}";
    }

    /**
     * Execute the query to fetch filtered articles.
     *
     * @param array $filters
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    private function queryFilteredArticles(array $filters)
    {
        $query = Article::select('id', 'title', 'description', 'author', 'thumbnail', 'published_at')
            ->orderBy('published_at', 'desc');

        // Apply user-specific filters if authenticated
        if (Auth::check()) {
            /** @var User $user */
            $user = Auth::user();

            $topicPreferences = $user->getTopicPreferencesArray();
            $sourcePreferences = $user->getSourcePreferencesArray();
            $authorPreferences = $user->getAuthorPreferencesArray();

            if (!empty($topicPreferences)) {
                $query->whereIn('topic', $topicPreferences);
            }

            if (!empty($sourcePreferences)) {
                $query->whereIn('source_name', $sourcePreferences);
            }

            if (!empty($authorPreferences)) {
                $query->whereIn('author', $authorPreferences);
            }
        }

        // Apply general filters
        if (!empty($filters['keyword'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('title', 'like', '%' . $filters['keyword'] . '%')
                  ->orWhere('description', 'like', '%' . $filters['keyword'] . '%');
            });
        }

        if (!empty($filters['date'])) {
            $query->whereRaw("DATE_FORMAT(published_at, '%d-%m-%Y') = ?", [$filters['date']]);
        }

        if (!empty($filters['category'])) {
            $query->where('topic', $filters['category']);
        }

        if (!empty($filters['source'])) {
            $query->where('source_name', $filters['source']);
        }

        $perPage = $filters['per_page'] ?? 10; // Default to 10 articles per page
        return $query->paginate($perPage);
    }

    /**
     * Fetch a single article by ID.
     *
     * @param int $id
     * @return Article|null
     */
    public function getArticleById(int $id)
    {
        return Article::find($id);
    }

    /**
     * Store or update an article in the database.
     *
     * @param array $articleData
     * @return Article
     */
    public function storeOrUpdate(array $articleData): Article
    {
        return Article::updateOrCreate(
            [
                'title'        => $articleData['title'],
                'source_name'  => $articleData['source_name'],
                'published_at' => $articleData['published_at'],
            ],
            $articleData
        );
    }

    /**
     * Fetch sources by topics.
     *
     * @param array $topics
     * @return array
     */
    public function fetchSourcesByTopics(array $topics): array
    {
        return Article::whereIn('topic', $topics)
            ->distinct()
            ->pluck('source_name')
            ->toArray();
    }

    /**
     * Fetch authors by topics and sources.
     *
     * @param array $topics
     * @param array $sources
     * @return array
     */
    public function fetchAuthorsByTopicsAndSources(array $topics, array $sources): array
    {
        return Article::whereIn('topic', $topics)
            ->whereIn('source_name', $sources)
            ->distinct()
            ->pluck('author')
            ->toArray();
    }
}