<?php

namespace App\Modules\Evaluation\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use Symfony\Component\HttpFoundation\Response;
use App\Modules\Evaluation\Services\NominationService;
use App\Modules\Evaluation\Requests\StoreNominationRequest;
use App\Modules\Evaluation\Requests\UpdateNominationRequest;
use App\Modules\Evaluation\Requests\PostponeNominationRequest;

class NominationController extends Controller
{
    protected NominationService $nominationService;

    /**
     * Inject the NominationService dependency.
     */
    public function __construct(NominationService $nominationService)
    {
        $this->nominationService = $nominationService;
    }

    /**
     * Store a new student evaluation containing examiner nominations.
     * 
     * @param \Illuminate\Http\Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated_request = StoreNominationRequest::validate($request);
            $evaluation = $this->nominationService->createNomination($validated_request);

            return $this->sendCreatedResponse($evaluation, 'Nomination created successfully!');
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
     * Update an existing student evaluation with new examiner nominations
     * 
     * @param \Illuminate\Http\Request $request
     * @param int $evaluationId
     * @return JsonResponse
     */
    public function update(Request $request, int $evaluationId): JsonResponse
    {
        try {
            $validated_request = UpdateNominationRequest::validate($request, $evaluationId);
            $evaluation = $this->nominationService->updateNomination( $validated_request);

            return $this->sendResponse($evaluation, 'Nomination updated successfully!');
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
     * Postpone a student's evaluation.
     * 
     * @OA\Post(
     *     path="/api/evaluations/nominations/{id}/postpone",
     *     summary="Postpone an evaluation",
     *     description="Postpone a student evaluation to a different period with a reason. All committee members (excluding office assistants) will receive email notifications.",
     *     tags={"Evaluations"},
     *     security={{"sanctumAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Evaluation ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"reason", "postponed_to"},
     *             @OA\Property(property="reason", type="string", maxLength=1000, description="Reason for postponement"),
     *             @OA\Property(property="postponed_to", type="string", format="date", description="New date for the evaluation (must be in the future)")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Evaluation postponed successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Nomination postponed successfully!"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Validation failed"),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error"
     *     )
     * )
     * 
     * @param \Illuminate\Http\Request $request
     * @param mixed $evaluationId
     * @return JsonResponse
     */
    public function postpone(Request $request, $evaluationId): JsonResponse
    {
        try {
            $validated_request = PostponeNominationRequest::validate($request, $evaluationId);
            $evaluation = $this->nominationService->postpone($validated_request);

            return $this->sendResponse($evaluation, 'Nomination postponed successfully!');
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
     * Display a listing of nominations.
     * 
     * @param \Illuminate\Http\Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $nominations = $this->nominationService->getNominations(
                $request->get('per_page', 10),
                $request->all()
            );
            return $this->sendResponse($nominations, 'Nominations retrieved successfully!');
        } catch (\Exception $e) {
            return $this->sendError(
                'An unexpected error occurred. Please try again later.',
                ['error' => $e->getMessage()],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Lock nominations to prevent further modifications.
     * 
     * @param \Illuminate\Http\Request $request
     * @return JsonResponse
     */
    public function lockNominations(Request $request): JsonResponse
    {
        try {
            $validated_request = $request->validate([
                'evaluation_ids' => ['required', 'array'],
                'evaluation_ids.*' => ['required', 'exists:student_evaluations,id'],
            ]);

            $lockedCount = $this->nominationService->lockNominations($validated_request['evaluation_ids']);

            return $this->sendResponse(
                ['locked_count' => $lockedCount], 
                "Successfully locked {$lockedCount} nomination(s)!"
            );
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
