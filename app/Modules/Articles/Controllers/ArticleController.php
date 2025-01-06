<?php

namespace App\Modules\Articles\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Modules\Articles\Models\Article;
use App\Modules\Articles\Models\Topic;
use Illuminate\Support\Facades\Validator;

class ArticleController extends Controller
{
    /**
     * Fetch articles with pagination.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $query = Article::select('id', 'title', 'description', 'author', 'thumbnail', 'published_at');
    
        // Filter by keyword (searching in title or description)
        if ($request->has('keyword')) {
            $query->where(function ($q) use ($request) {
                $q->where('title', 'like', '%' . $request->keyword . '%')
                  ->orWhere('description', 'like', '%' . $request->keyword . '%');
            });
        }
    
        // Filter by date (published_at)
        if ($request->has('date')) {
            $query->whereRaw("DATE_FORMAT(published_at, '%d-%m-%Y') = ?", [$request->date]);
        }
    
        // Filter by category (topic)
        if ($request->has('category')) {
            $query->where('topic', $request->category);
        }
    
        // Filter by source (source_name)
        if ($request->has('source')) {
            $query->where('source_name', $request->source);
        }
    
        // Paginate the results
        $articles = $query->paginate(10);
    
        return $this->sendResponse($articles, 'Articles fetched successfully.');
    }

    /**
     * Fetch a single article by ID.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        $article = Article::find($id);

        if (!$article) {
            return response()->json(['error' => 'Article not found'], 404);
        }
        return $this->sendResponse(["detail" => $article], 'Article detail fetched successfully.');
    }

    /**
     * Get all topics.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getTopics()
    {
        // Fetch topics with pagination
        $topics = Topic::pluck("name");
        return $this->sendResponse($topics, 'Topics fetched successfully.');
    }
    /**
     * Get all sources.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getSources()
    {
        // Fetch sources with pagination
        $sources = Article::whereNotIn('source_name', ["[Removed]"])->distinct()->pluck("source_name");
        return $this->sendResponse($sources, 'Sources fetched successfully.');
    }
}