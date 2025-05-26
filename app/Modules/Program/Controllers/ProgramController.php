<?php

namespace App\Modules\Program\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;
use App\Modules\Program\Requests\StoreProgramRequest;
use App\Modules\Program\Requests\UpdateProgramRequest;
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
            $programs = $this->programService->getPrograms(
                $request->get('per_page', 10),
                $request->all()
            );
            return response()->json($programs);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to fetch programs.',
                'message' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
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
            return response()->json($program, Response::HTTP_CREATED);
        } catch (ValidationException $e) {
            return response()->json([
                'error' => 'Validation failed.',
                'messages' => $e->errors(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to create program.',
                'message' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
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
            return response()->json($program);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to fetch program.',
                'message' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
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
            return response()->json($program);
        } catch (ValidationException $e) {
            return response()->json([
                'error' => 'Validation failed.',
                'messages' => $e->errors(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to update program.',
                'message' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
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
            return response()->json(['message' => 'Program deleted successfully.']);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to delete program.',
                'message' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Assign lecturers to a program.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function assignLecturers(Request $request, int $id): JsonResponse
    {
        try {
            $data = $request->all();
            $data['program_id'] = $id;

            $validator = \Validator::make($data, [
                'program_id' => ['required', 'exists:programs,id'],
                'lecturer_ids' => ['required', 'array'],
                'lecturer_ids.*' => ['integer', 'exists:lecturers,id'],
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'error' => 'Validation failed.',
                    'messages' => $validator->errors(),
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            // Assuming assignLecturers method exists in the service
            $program = $this->programService->assignLecturers($id, $validator->validated());

            return response()->json([
                'message' => 'Lecturers assigned successfully.',
                'data' => $program,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to assign lecturers.',
                'message' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}