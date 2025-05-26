<?php

namespace App\Modules\Evaluation\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Evaluation\Requests\StoreEvaluationRequest;
use App\Modules\Evaluation\Requests\UpdateEvaluationRequest;
use App\Modules\Evaluation\Requests\PostponeEvaluationRequest;
use App\Modules\Evaluation\Services\EvaluationService;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class EvaluationController extends Controller
{
    protected EvaluationService $evaluationService;

    public function __construct(EvaluationService $evaluationService)
    {
        $this->evaluationService = $evaluationService;
    }

    public function store(StoreEvaluationRequest $request)
    {
        try {
            $data = $request->validated();
            $evaluation = $this->evaluationService->store($data);

            return response()->json([
                'message' => 'Evaluation created successfully.',
                'data' => $evaluation,
            ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to create evaluation',
                'message' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function update(UpdateEvaluationRequest $request, int $id)
    {
        try {
            $data = $request->validated();
            $evaluation = $this->evaluationService->update($id, $data);

            return response()->json([
                'message' => 'Evaluation updated successfully.',
                'data' => $evaluation,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to update evaluation',
                'message' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function postpone(PostponeEvaluationRequest $request, int $id)
    {
        try {
            $evaluation = $this->evaluationService->postpone($id, \App\Enums\NominationStatus::Postponed);

            return response()->json([
                'message' => 'Evaluation postponed successfully.',
                'data' => $evaluation,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to postpone evaluation',
                'message' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function lock(int $id)
    {
        try {
            $evaluation = $this->evaluationService->lock($id, auth()->id(), now());

            return response()->json([
                'message' => 'Evaluation nomination locked successfully.',
                'data' => $evaluation,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to lock evaluation nomination',
                'message' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function getEligibleStudents(int $rsId)
    {
        try {
            $students = $this->evaluationService->getEligibleStudents($rsId);

            return response()->json([
                'message' => 'Eligible students retrieved successfully.',
                'data' => $students,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to retrieve eligible students',
                'message' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Assign examiners to an evaluation.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function assignExaminers(Request $request, int $id)
    {
        try {
            $data = $request->all();
            $data['evaluation_id'] = $id;

            $validator = Validator::make($data, [
                'evaluation_id' => ['required', 'exists:student_evaluations,id'],
                'examiner1_id' => ['nullable', 'integer', 'exists:lecturers,id'],
                'examiner2_id' => ['nullable', 'integer', 'exists:lecturers,id'],
                'examiner3_id' => ['nullable', 'integer', 'exists:lecturers,id'],
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'error' => 'Validation failed',
                    'messages' => $validator->errors(),
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            $evaluation = $this->evaluationService->assignExaminers($id, $validator->validated());

            return response()->json([
                'message' => 'Examiners assigned successfully.',
                'data' => $evaluation,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to assign examiners',
                'message' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
