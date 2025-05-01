<?php

namespace App\Modules\Articles\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Modules\Articles\Services\ArticleService;
use Illuminate\Validation\ValidationException;

/**
 * @OA\Tag(
 *     name="Articles",
 *     description="API Endpoints related to Articles"
 * )
 */
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


    public function index(Request $request): JsonResponse
    {
        try {
            $filters = $request->all();
            $articles = $this->articleService->getArticles($filters);

            return $this->sendResponse($articles, 'Articles fetched successfully.');
        } catch (ValidationException $e) {
            return $this->sendValidationError($e->errors());
        } catch (\Exception $e) {
            return $this->sendError(
                'Failed to fetch articles. Please try again later.',
                ['error' => $e->getMessage()],
                500
            );
        }
    }

    public function show(int $id): JsonResponse
    {
        try {
            $article = $this->articleService->getArticleById($id);

            if (!$article) {
                return $this->sendError('Article not found.', [], 404);
            }

            return $this->sendResponse(['detail' => $article], 'Article detail fetched successfully.');
        } catch (\Exception $e) {
            return $this->sendError(
                'Failed to fetch the article. Please try again later.',
                ['error' => $e->getMessage()],
                500
            );
        }
    }

    public function getSourcesByTopics(Request $request)
    {
        try {
            $topics = $request->input('topics', []);

            if (!is_array($topics)) {
                throw new \InvalidArgumentException('The topics parameter must be an array.');
            }

            if (empty($topics)) {
                return $this->sendError('Topics are required.', [], 400);
            }

            $sources = $this->articleService->getSourcesByTopics($topics);

            return $this->sendResponse($sources, 'Sources fetched successfully.');
        } catch (\InvalidArgumentException $e) {
            return $this->sendError($e->getMessage(), [], 400);
        } catch (\Exception $e) {
            return $this->sendError(
                'Failed to fetch sources. Please try again later.',
                ['error' => $e->getMessage()],
                500
            );
        }
    }

    public function getAuthorsByTopicsAndSources(Request $request)
    {
        try {
            $topics = $request->input('topics', []);
            $sources = $request->input('sources', []);

            if (!is_array($topics)) {
                throw new \InvalidArgumentException('The topics parameter must be an array.');
            }

            if (!is_array($sources)) {
                throw new \InvalidArgumentException('The sources parameter must be an array.');
            }

            if (empty($topics) || empty($sources)) {
                return $this->sendError('Topics and sources are required.', [], 400);
            }

            $authors = $this->articleService->getAuthorsByTopicsAndSources($topics, $sources);

            return $this->sendResponse($authors, 'Authors fetched successfully.');
        } catch (\InvalidArgumentException $e) {
            return $this->sendError($e->getMessage(), [], 400);
        } catch (\Exception $e) {
            return $this->sendError(
                'Failed to fetch authors. Please try again later.',
                ['error' => $e->getMessage()],
                500
            );
        }
    }
}