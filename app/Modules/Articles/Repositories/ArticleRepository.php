<?php

namespace App\Modules\Articles\Repositories;

use Illuminate\Support\Facades\Auth;
use App\Modules\Articles\Models\Article;
use App\Modules\Articles\Models\UserPreference;

class ArticleRepository
{   
    /**
     * Get articles with filters and pagination.
     *
     * @param array $filters
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getFilteredArticles(array $filters)
    {
        $query = Article::select('id', 'title', 'description', 'author', 'thumbnail', 'published_at')
            ->orderBy('published_at', 'desc');
    
        // Check if user is authenticated
        if (Auth::check()) {
            $userId = Auth::id();
    
            // Fetch user preferences
            $preferences = UserPreference::where('user_id', $userId)->first();
    
            if ($preferences) {
                // Apply user preferences to the query
                if (!empty($preferences->topics)) {
                    $query->whereIn('topic', $preferences->topics);
                }
    
                if (!empty($preferences->sources)) {
                    $query->whereIn('source_name', $preferences->sources);
                }
    
                if (!empty($preferences->categories)) {
                    $query->whereIn('category', $preferences->categories);
                }
            }
        }
    
        // Apply additional filters
        if (!empty($filters['keyword'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('title', 'like', '%' . $filters['keyword'] . '%');
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
    
        $perPage = $filters['per_page'] ?? 10; // Default to 10 per page
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
     * Get all distinct article sources.
     *
     * @return array
     */
    public function getSources()
    {
        return Article::whereNotIn('source_name', ['[Removed]'])
            ->distinct()
            ->pluck('source_name')
            ->toArray();
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
}