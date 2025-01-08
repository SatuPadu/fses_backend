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


    /**
     * @OA\Get(
     *     path="/article",
     *     tags={"Articles"},
     *     summary="Fetch articles/persnalised feed (if the token is passed) with optional filters and pagination",
     *     security={
    *         {"sanctumAuth": {}}
    *     },
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         required=false,
     *         description="Page number for pagination",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         required=false,
     *         description="Number of articles per page",
     *         @OA\Schema(type="integer", example=10)
     *     ),
     *     @OA\Parameter(
     *         name="source",
     *         in="query",
     *         required=false,
     *         description="Filter articles by source",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="category",
     *         in="query",
     *         required=false,
     *         description="Filter articles by category",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="date",
     *         in="query",
     *         required=false,
     *         description="Filter articles by date in YYYY-MM-DD format",
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Parameter(
     *         name="keyword",
     *         in="query",
     *         required=false,
     *         description="Search for articles containing the keyword",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Articles fetched successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="success",
     *                 type="boolean",
     *                 example=true
     *             ),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="items",
     *                     type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="id", type="integer", example=341),
     *                         @OA\Property(property="title", type="string", example="Quit it! From vaping to doomscrolling, 10 bad habits and how to break them"),
     *                         @OA\Property(property="description", type="string", example="Procrastinating? Online shopping too much? Always cancelling? These top tips from experts will help you change bad behaviour for the better"),
     *                         @OA\Property(property="author", type="string", example="Kate Wills"),
     *                         @OA\Property(property="thumbnail", type="string", example="https://media.example.com/image.jpg"),
     *                         @OA\Property(property="published_at", type="string", format="date-time", example="2025-01-04T12:00:49.000000Z")
     *                     )
     *                 ),
     *                 @OA\Property(
     *                     property="pagination",
     *                     type="object",
     *                     @OA\Property(property="page", type="integer", example=1),
     *                     @OA\Property(property="last_page", type="integer", example=1),
     *                     @OA\Property(property="per_page", type="integer", example=20),
     *                     @OA\Property(property="total", type="integer", example=2),
     *                     @OA\Property(property="from", type="integer", example=1),
     *                     @OA\Property(property="to", type="integer", example=2)
     *                 )
     *             ),
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 example="Articles fetched successfully."
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Validation Error"),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Failed to fetch articles",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Failed to fetch articles. Please try again later.")
     *         )
     *     )
     * )
     */
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

    /**
     * @OA\Get(
     *     path="/article/detail/{id}",
     *     tags={"Articles"},
     *     summary="Fetch a single article by ID",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID of the article",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Article detail fetched successfully",
     *         @OA\JsonContent(type="object")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Article not found"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Failed to fetch the article"
     *     )
     * )
     */
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

    /**
     * @OA\Get(
     *     path="/article/sources",
     *     tags={"Articles"},
     *     summary="Get sources by topics",
     *     @OA\Parameter(
     *         name="topics[]",
     *         in="query",
     *         required=true,
     *         description="Array of topics, e.g., topics[]=About",
     *         @OA\Schema(
     *             type="array",
     *             @OA\Items(type="string")
     *         ),
     *         style="form",
     *         explode=true
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Sources fetched successfully",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Invalid request or missing topics"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Failed to fetch sources"
     *     )
     * )
     */
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

    /**
     * @OA\Get(
     *     path="/article/authors",
     *     tags={"Articles"},
     *     summary="Get authors by topics and sources",
     *     @OA\Parameter(
     *         name="topics",
     *         in="query",
     *         required=true,
     *         description="Array of topics",
     *         @OA\Schema(type="array", @OA\Items(type="string"))
     *     ),
     *     @OA\Parameter(
     *         name="sources",
     *         in="query",
     *         required=true,
     *         description="Array of sources",
     *         @OA\Schema(type="array", @OA\Items(type="string"))
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Authors fetched successfully",
     *         @OA\JsonContent(type="array", @OA\Items(type="string"))
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Invalid request or missing parameters"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Failed to fetch authors"
     *     )
     * )
     */
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