<?php

namespace App\Modules\Articles\Controllers;

use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Modules\Articles\Services\TopicService;

/**
 * @OA\Tag(
 *     name="Topics",
 *     description="API Endpoints related to Topics"
 * )
 */
class TopicController extends Controller
{
    private TopicService $topicService;

    /**
     * Inject the TopicService dependency.
     *
     * @param TopicService $topicService
     */
    public function __construct(TopicService $topicService)
    {
        $this->topicService = $topicService;
    }

    /**
     * @OA\Get(
     *     path="/article/topics",
     *     tags={"Articles"},
     *     summary="Get all topics",
     *     description="Retrieve all available topics",
     *     @OA\Response(
     *         response=200,
     *         description="Topics fetched successfully",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", description="Topic ID"),
     *                 @OA\Property(property="name", type="string", description="Topic name")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Failed to fetch topics",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Failed to fetch topics. Please try again later."),
     *             @OA\Property(property="data", type="object", nullable=true)
     *         )
     *     )
     * )
     */
    public function getTopics(): JsonResponse
    {
        try {
            $topics = $this->topicService->getTopics();
            return $this->sendResponse($topics, 'Topics fetched successfully.');
        } catch (\RuntimeException $e) {
            return $this->sendError(
                $e->getMessage(),
                ['error' => $e->getMessage()],
                500
            );
        } catch (\Exception $e) {
            return $this->sendError(
                'Failed to fetch topics. Please try again later.',
                ['error' => $e->getMessage()],
                500
            );
        }
    }
}