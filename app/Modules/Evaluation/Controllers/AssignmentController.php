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
     * Receives collection of objects containing chairperson assignments.
     * 
     * @param \Illuminate\Http\Request $request
     * @throws \Exception
     * @return JsonResponse
     */
    public function assign(Request $request): JsonResponse
    {
        try {

            $validated_request = UpdateAssignmentRequest::validate($request);
            $evaluation = $this->assignmentService->assign($validated_request);

            if ($evaluation['status'] == 'success') {
                return $this->sendResponse($evaluation, 'Assignment updated successfully.');
            }
            else {
                throw new \Exception;
            }
            
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
     * Receive evaluation ID to lock nominations.
     * 
     * @param mixed $evaluationId
     * @return JsonResponse
     */
    public function lock($evaluationId): JsonResponse
    {
        try {
            $evaluation = $this->assignmentService->lock($evaluationId);

            return $this->sendResponse($evaluation, 'Evaluation nomination locked successfully.');
        } catch (\Exception $e) {
            return $this->sendError(
                'An unexpected error occurred. Please try again later.',
                ['error' => $e->getMessage()],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Display a listing of assignments.
     * 
     * @param \Illuminate\Http\Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $assignments = $this->assignmentService->getAssignments(
                $request->get('per_page', 10),
                $request->all()
            );
            return $this->sendResponse($assignments, 'Assignments retrieved successfully!');
        } catch (\Exception $e) {
            return $this->sendError(
                'An unexpected error occurred. Please try again later.',
                ['error' => $e->getMessage()],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Update an existing chairperson assignment.
     * 
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $validated_request = UpdateAssignmentRequest::validate($request, $id);
            $evaluation = $this->assignmentService->assign([$validated_request]);

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
}
