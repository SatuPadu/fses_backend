<?php

namespace App\Modules\Articles\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Modules\Articles\Services\ArticleService;
use Illuminate\Validation\ValidationException;

class ArticleController extends Controller
{
    protected ArticleService $articleService;

    /**
     * Inject the ArticleService dependency.
     *
     * @param ArticleService $articleService
     */
    public function __construct(ArticleService $articleService)
    {
        $this->articleService = $articleService;
    }

    /**
     * Fetch articles with optional filters and pagination.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            // Fetch articles with applied filters
            $filters = $request->all();
            $articles = $this->articleService->getArticles($filters);

            return $this->sendResponse($articles, 'Articles fetched successfully.');
        } catch (ValidationException $e) {
            // Handle validation errors
            return $this->sendValidationError($e->errors());
        } catch (\Exception $e) {
            // Handle unexpected errors
            return $this->sendError(
                'Failed to fetch articles. Please try again later.',
                ['error' => $e->getMessage()],
                500
            );
        }
    }

    /**
     * Fetch a single article by ID.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        try {
            // Fetch the article by ID
            $article = $this->articleService->getArticleById($id);

            if (!$article) {
                return $this->sendError('Article not found.', [], 404);
            }

            return $this->sendResponse(['detail' => $article], 'Article detail fetched successfully.');
        } catch (\Exception $e) {
            // Handle unexpected errors
            return $this->sendError(
                'Failed to fetch the article. Please try again later.',
                ['error' => $e->getMessage()],
                500
            );
        }
    }

    /**
     * Get sources by topics.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getSourcesByTopics(Request $request)
    {
        try {
            $topics = $request->input('topics', []);
            if (empty($topics)) {
                return $this->sendError('Topics are required.', [], 400);
            }

            $sources = $this->articleService->getSourcesByTopics($topics);

            return $this->sendResponse($sources, 'Sources fetched successfully.');
        } catch (\Exception $e) {
            return $this->sendError(
                'Failed to fetch sources. Please try again later.',
                ['error' => $e->getMessage()],
                500
            );
        }
    }

    /**
     * Get authors by topics and sources.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAuthorsByTopicsAndSources(Request $request)
    {
        try {
            $topics = $request->input('topics', []);
            $sources = $request->input('sources', []);
            if (empty($topics) || empty($sources)) {
                return $this->sendError('Topics and sources are required.', [], 400);
            }

            $authors = $this->articleService->getAuthorsByTopicsAndSources($topics, $sources);

            return $this->sendResponse($authors, 'Authors fetched successfully.');
        } catch (\Exception $e) {
            return $this->sendError(
                'Failed to fetch authors. Please try again later.',
                ['error' => $e->getMessage()],
                500
            );
        }
    }
}