<?php

namespace App\Modules\Evaluation\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Evaluation\Requests\UpdateAssignmentRequest;
use App\Modules\Evaluation\Services\AssignmentService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Http\Request;

class AssignmentController extends Controller
{
    protected AssignmentService $assignmentService;

    /**
     * Inject the AssignmentService dependency.
     */
    public function __construct(AssignmentService $assignmentService)
    {
        $this->assignmentService = $assignmentService;
    }

    /**
     * Update an existing chairperson assignment.
     * 
     * @param \Illuminate\Http\Request $request
     * @return JsonResponse
     */
    public function update(Request $request): JsonResponse
    {
        try {
            $validated_request = UpdateAssignmentRequest::validate($request);
            $evaluation = $this->assignmentService->assign($validated_request['assignments']);
            return $this->sendResponse($evaluation, 'Assignment updated successfully!');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->sendValidationError($e->errors());
        } catch (\Exception $e) {
            return $this->sendError(
                'An unexpected error occurred. Please try again later.',
                ['error' => $e->getMessage()],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Get eligible chairperson suggestions for an evaluation.
     *
     * @param \Illuminate\Http\Request $request
     * @return JsonResponse
     */
    public function getChairpersonSuggestions(Request $request): JsonResponse
    {
        $evaluationId = $request->query('evaluation_id');
        if (!$evaluationId) {
            return response()->json(['error' => 'evaluation_id is required'], 422);
        }

        try {
            $eligibleChairpersons = $this->assignmentService->getEligibleChairpersons($evaluationId);
            return response()->json($eligibleChairpersons);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Get all eligible chairpersons for future evaluations (no evaluation context).
     *
     * @return JsonResponse
     */
    public function chairpersonSuggestions(): JsonResponse
    {
        try {
            $eligibleChairpersons = $this->assignmentService->chairpersonSuggestions();
            return $this->sendResponse($eligibleChairpersons, 'Eligible chairpersons retrieved successfully!');
        } catch (\Exception $e) {
            return $this->sendError(
                'An unexpected error occurred. Please try again later.',
                ['error' => $e->getMessage()],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }
}
