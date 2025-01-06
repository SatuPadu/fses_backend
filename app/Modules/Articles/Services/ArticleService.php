<?php

namespace App\Modules\Articles\Services;

use App\Modules\Articles\Repositories\ArticleRepository;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

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
            return $this->articleRepo->getArticleById($id);
        } catch (\Exception $e) {
            throw new \RuntimeException('Unable to fetch article details. Please try again later.');
        }
    }

    /**
     * Get all distinct sources.
     *
     * @return array
     */
    public function getSources()
    {
        try {
            return $this->articleRepo->getSources();
        } catch (\Exception $e) {
            throw new \RuntimeException('Unable to fetch sources. Please try again later.');
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

        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new \RuntimeException('Unable to process and store article. Please try again later.');
        }
    }
}