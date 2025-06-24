<?php

namespace App\Modules\Evaluation\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Evaluation\Services\ExaminerSuggestionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @OA\Tag(
 *     name="Examiner Suggestions",
 *     description="API Endpoints for examiner suggestions based on eligibility rules"
 * )
 */
class ExaminerSuggestionController extends Controller
{
    protected ExaminerSuggestionService $examinerSuggestionService;

    /**
     * Inject the ExaminerSuggestionService dependency.
     */
    public function __construct(ExaminerSuggestionService $examinerSuggestionService)
    {
        $this->examinerSuggestionService = $examinerSuggestionService;
    }

    /**
     * Get suggestions for Examiner 1
     * 
     * @OA\Get(
     *     path="/api/examiner-suggestions/examiner1/{studentId}",
     *     summary="Get Examiner 1 suggestions",
     *     tags={"Examiner Suggestions"},
     *     @OA\Parameter(
     *         name="studentId",
     *         in="path",
     *         required=true,
     *         description="Student ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Examiner 1 suggestions retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Lecturer")),
     *             @OA\Property(property="message", type="string", example="Examiner 1 suggestions retrieved successfully!")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Student not found"
     *     )
     * )
     */
    public function getExaminer1Suggestions(int $studentId): JsonResponse
    {
        try {
            $suggestions = $this->examinerSuggestionService->getExaminer1Suggestions($studentId);
            return $this->sendResponse($suggestions, 'Examiner 1 suggestions retrieved successfully!');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->sendError(
                'Student not found',
                ['error' => 'Student not found'],
                Response::HTTP_NOT_FOUND
            );
        } catch (\Exception $e) {
            return $this->sendError(
                'Failed to retrieve examiner 1 suggestions',
                ['error' => $e->getMessage()],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Get suggestions for Examiner 2
     * 
     * @OA\Get(
     *     path="/api/examiner-suggestions/examiner2/{studentId}",
     *     summary="Get Examiner 2 suggestions",
     *     tags={"Examiner Suggestions"},
     *     @OA\Parameter(
     *         name="studentId",
     *         in="path",
     *         required=true,
     *         description="Student ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Examiner 2 suggestions retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Lecturer")),
     *             @OA\Property(property="message", type="string", example="Examiner 2 suggestions retrieved successfully!")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Student not found"
     *     )
     * )
     */
    public function getExaminer2Suggestions(int $studentId): JsonResponse
    {
        try {
            $suggestions = $this->examinerSuggestionService->getExaminer2Suggestions($studentId);
            return $this->sendResponse($suggestions, 'Examiner 2 suggestions retrieved successfully!');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->sendError(
                'Student not found',
                ['error' => 'Student not found'],
                Response::HTTP_NOT_FOUND
            );
        } catch (\Exception $e) {
            return $this->sendError(
                'Failed to retrieve examiner 2 suggestions',
                ['error' => $e->getMessage()],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Get suggestions for Examiner 3
     * 
     * @OA\Get(
     *     path="/api/examiner-suggestions/examiner3/{studentId}",
     *     summary="Get Examiner 3 suggestions",
     *     tags={"Examiner Suggestions"},
     *     @OA\Parameter(
     *         name="studentId",
     *         in="path",
     *         required=true,
     *         description="Student ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Examiner 3 suggestions retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Lecturer")),
     *             @OA\Property(property="message", type="string", example="Examiner 3 suggestions retrieved successfully!")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Student not found"
     *     )
     * )
     */
    public function getExaminer3Suggestions(int $studentId): JsonResponse
    {
        try {
            $suggestions = $this->examinerSuggestionService->getExaminer3Suggestions($studentId);
            return $this->sendResponse($suggestions, 'Examiner 3 suggestions retrieved successfully!');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->sendError(
                'Student not found',
                ['error' => 'Student not found'],
                Response::HTTP_NOT_FOUND
            );
        } catch (\Exception $e) {
            return $this->sendError(
                'Failed to retrieve examiner 3 suggestions',
                ['error' => $e->getMessage()],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }
} 