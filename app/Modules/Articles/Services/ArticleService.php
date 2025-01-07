<?php

namespace App\Modules\Articles\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use App\Helpers\SanitizeResponseHelper;
use Illuminate\Validation\ValidationException;
use App\Modules\Articles\Repositories\ArticleRepository;

class ArticleService
{
    private ArticleRepository $articleRepo;

    /**
     * Inject the ArticleRepository dependency.
     */
    public function __construct(ArticleRepository $articleRepo)
    {
        $this->articleRepo = $articleRepo;
    }

    /**
     * Fetch articles with optional filters and pagination.
     *
     * @param array $filters
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getArticles(array $filters)
    {
        try {
            return $this->articleRepo->getFilteredArticles($filters);
        } catch (\Exception $e) {
            throw new \RuntimeException('Unable to fetch articles. Please try again later.');
        }
    }

    /**
     * Fetch a single article by ID.
     *
     * @param int $id
     * @return \App\Modules\Articles\Models\Article|null
     */
    public function getArticleById(int $id)
    {
        try {
            $cacheKey = "article_{$id}";

            return Cache::remember($cacheKey, now()->addHours(1), function () use ($id) {
                return $this->articleRepo->getArticleById($id);
            });
        } catch (\Exception $e) {
            throw new \RuntimeException('Unable to fetch article details. Please try again later.');
        }
    }

    /**
     * Process and store or update an article.
     *
     * @param array $articleData
     * @return void
     */
    public function processAndStoreArticle(array $articleData): void
    {
        try {
            // Parse and format the published_at date
            if (!empty($articleData['published_at'])) {
                $date = Carbon::parse($articleData['published_at']);
                $articleData['published_at'] = $date->format('Y-m-d H:i:s');
            }

            // Delegate to the repository for database operations
            $this->articleRepo->storeOrUpdate($articleData);

            // Clear relevant caches
            Cache::forget('articles');
            if (isset($articleData['id'])) {
                Cache::forget("article_{$articleData['id']}");
            }
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new \RuntimeException('Unable to process and store article. Please try again later.');
        }
    }

    /**
     * Get sources by topics.
     *
     * @param array $topics
     * @return array
     */
    public function getSourcesByTopics(array $topics): array
    {
        try {
            $cacheKey = $this->getCacheKey('sources_by_topics', ['topics' => $topics]);

            return Cache::remember($cacheKey, now()->addHours(1), function () use ($topics) {
                return $this->articleRepo->fetchSourcesByTopics($topics);
            });
        } catch (\Exception $e) {
            throw new \RuntimeException('Unable to fetch sources. Please try again later.');
        }
    }

    /**
     * Get authors by topics and sources.
     *
     * @param array $topics
     * @param array $sources
     * @return array
     */
    public function getAuthorsByTopicsAndSources(array $topics, array $sources): array
    {
        try {
            $cacheKey = $this->getCacheKey('authors_by_topics_and_sources', [
                'topics' => $topics,
                'sources' => $sources,
            ]);

            return Cache::remember($cacheKey, now()->addHours(1), function () use ($topics, $sources) {
                return SanitizeResponseHelper::sanitizeAuthors(
                    $this->articleRepo->fetchAuthorsByTopicsAndSources($topics, $sources)
                );
            });
        } catch (\Exception $e) {
            throw new \RuntimeException('Unable to fetch authors. Please try again later.');
        }
    }

    /**
     * Generate a cache key based on a prefix and parameters.
     *
     * @param string $prefix
     * @param array $params
     * @return string
     */
    private function getCacheKey(string $prefix, array $params): string
    {
        ksort($params);
        return $prefix . '_' . md5(json_encode($params));
    }
}