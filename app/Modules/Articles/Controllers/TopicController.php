<?php

namespace App\Modules\Articles\Controllers;

use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Modules\Articles\Services\TopicService;

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
     * Get all topics.
     *
     * @return JsonResponse
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