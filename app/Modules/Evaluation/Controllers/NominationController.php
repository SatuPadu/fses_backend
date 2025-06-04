<?php

namespace App\Modules\Evaluation\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;
use App\Modules\Evaluation\Services\NominationService;
use App\Modules\Evaluation\Requests\StoreEvaluationRequest;
use App\Modules\Evaluation\Requests\StoreNominationRequest;
use App\Modules\Evaluation\Requests\UpdateEvaluationRequest;
use App\Modules\Evaluation\Requests\UpdateNominationRequest;
use App\Modules\Evaluation\Requests\PostponeEvaluationRequest;
use App\Modules\Evaluation\Requests\PostponeNominationRequest;

class NominationController extends Controller
{
    protected NominationService $nominationService;

    public function __construct(NominationService $nominationService)
    {
        $this->nominationService = $nominationService;
    }

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

    public function update(Request $request, int $evaluationId)
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

    public function postpone(Request $request, $evaluationId)
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
}
