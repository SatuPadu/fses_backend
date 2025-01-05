<?php

namespace App\Modules\Articles\Repositories;

use App\Modules\Articles\Models\Article;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class ArticleRepository
{
    /**
     * Store or update an article using the provided data.
     *
     * @param  array  $articleData
     * @return \App\Modules\Articles\Models\Article
     */
    public function storeOrUpdate(array $articleData)
    {
        // Check if we have a published_at date to parse
        if (!empty($articleData['published_at'])) {
            try {
                // Parse the given date (e.g. "2025-01-03T02:30:00Z") using Carbon
                $date = Carbon::parse($articleData['published_at']);
                
                // Convert it to the MySQL-friendly format: "Y-m-d H:i:s"
                $articleData['published_at'] = $date->format('Y-m-d H:i:s');
            } catch (\Exception $e) {
                $articleData['published_at'] = now()->format('Y-m-d H:i:s');
            }
        }
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