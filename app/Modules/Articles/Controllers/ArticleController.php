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
        $articles = Article::paginate(10); // Adjust per-page count as needed
        return response()->json($articles, 200);
    }

    /**
     * Search articles based on keyword, date, category, or source.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function search(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'keyword' => 'nullable|string',
            'date' => 'nullable|date',
            'category' => 'nullable|string',
            'source' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $query = Article::query();

        if ($request->has('keyword')) {
            $query->where('title', 'like', '%' . $request->keyword . '%')
                ->orWhere('content', 'like', '%' . $request->keyword . '%');
        }

        if ($request->has('date')) {
            $query->whereDate('published_at', $request->date);
        }

        if ($request->has('category')) {
            $query->where('category', $request->category);
        }

        if ($request->has('source')) {
            $query->where('source', $request->source);
        }

        $articles = $query->paginate(10); // Adjust per-page count as needed
        return response()->json($articles, 200);
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

        return response()->json($article, 200);
    }

    /**
     * Get all topics in a paginated format.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getTopics(Request $request)
    {
        // Validate pagination inputs (optional)
        $validated = $request->validate([
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $perPage = $validated['per_page'] ?? 10; // Default to 10 items per page

        // Fetch topics with pagination
        $topics = Topic::select("id", "name")->paginate($perPage);
        return $this->sendResponse($topics, 'Topics fetched successfully.');
    }
}