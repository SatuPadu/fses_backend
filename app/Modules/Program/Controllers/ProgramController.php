<?php

namespace App\Modules\Program\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;
use App\Modules\Program\Requests\StoreProgramRequest;
use App\Modules\Program\Requests\UpdateProgramRequest;
use App\Modules\Program\Requests\ProgramGetRequest;
use App\Modules\Program\Services\ProgramService;

/**
 * @OA\Tag(
 *     name="Program Management",
 *     description="API Endpoints related to Program Management involving Programs"
 * )
 */
class ProgramController extends Controller
{
    protected $programService;

    /**
     * ProgramController constructor.
     *
     * @param ProgramService $programService
     */
    public function __construct(ProgramService $programService)
    {
        $this->programService = $programService;
    }

    /**
     * Display a listing of programs
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $validated_request = ProgramGetRequest::validate($request);
            $programs = $this->programService->getPrograms(
                $validated_request['per_page'] ?? 10,
                $validated_request
            );
            return $this->sendResponse($programs, 'Program list retrieved successfully!');
        } catch (ValidationException $e) {
            return $this->sendValidationError($e->errors());
        } catch (\Exception $e) {
            return $this->sendError(
                'Failed to fetch programs.',
                ['error' => $e->getMessage()],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * @route POST /api/programs
     * @description Store a new program
     */
    public function store(StoreProgramRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();
            $program = $this->programService->create($validated);
            return $this->sendCreatedResponse($program, 'Program added successfully!');
        } catch (ValidationException $e) {
            return $this->sendValidationError($e->errors());
        } catch (\Exception $e) {
            return $this->sendError(
                'Failed to create program.',
                ['error' => $e->getMessage()],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * @route GET /api/programs/{id}
     * @description Show a specific program by ID
     */
    public function show($id): JsonResponse
    {
        try {
            $program = $this->programService->getById($id);
            return $this->sendResponse($program, 'Program details retrieved successfully!');
        } catch (\Exception $e) {
            return $this->sendError(
                'Failed to fetch program.',
                ['error' => $e->getMessage()],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * @route PUT /api/programs/{id}
     * @description Update a specific program by ID
     */
    public function update(UpdateProgramRequest $request, $id): JsonResponse
    {
        try {
            $validated = $request->validated();
            $program = $this->programService->update($id, $validated);
            return $this->sendResponse($program, 'Program info updated successfully!');
        } catch (ValidationException $e) {
            return $this->sendValidationError($e->errors());
        } catch (\Exception $e) {
            return $this->sendError(
                'Failed to update program.',
                ['error' => $e->getMessage()],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * @route DELETE /api/programs/{id}
     * @description Delete a specific program by ID
     */
    public function destroy($id): JsonResponse
    {
        try {
            $this->programService->delete($id);
            return $this->sendResponse(null, 'Program info deleted successfully!');
        } catch (\Exception $e) {
            return $this->sendError(
                'Failed to delete program.',
                ['error' => $e->getMessage()],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }
}