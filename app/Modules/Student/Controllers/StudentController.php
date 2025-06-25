<?php

namespace App\Modules\Student\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Student\Requests\StoreStudentRequest;
use App\Modules\Student\Requests\UpdateStudentRequest;
use App\Modules\Student\Services\StudentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Throwable;

/**
 * @OA\Tag(
 *     name="Student Management",
 *     description="API Endpoints related to Student Management involving Students"
 * )
 */
class StudentController extends Controller
{
    private StudentService $studentService;

    public function __construct(StudentService $studentService)
    {
        $this->studentService = $studentService;
        $this->middleware('jwt.verify');
    }

    /**
     * Display a listing of students
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $students = $this->studentService->getAllStudents(
                $request->get('per_page', 10),
                $request->all()
            );
            return response()->json($students);
        } catch (Throwable $e) {
            return response()->json([
                'error' => 'Failed to retrieve students',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created student in the database.
     *
     * @param StoreStudentRequest $request
     * @return JsonResponse
     */
    public function store(StoreStudentRequest $request): JsonResponse
    {
        try {
            $student = $this->studentService->createStudent($request->validated());
            return response()->json($student, 201);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'error' => 'Supervisor not found'
            ], 404);
        } catch (QueryException $e) {
            return response()->json([
                'error' => 'Database error',
                'message' => $e->getMessage()
            ], 400);
        } catch (Throwable $e) {
            return response()->json([
                'error' => 'Unexpected error',
                'message' => $e->getMessage()
            ], 500);
        }
    }


    /**
     * Display the specified student.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show($id): JsonResponse
    {
        try {
            $student = $this->studentService->getStudentById($id);
            return response()->json($student);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'error' => 'Student not found'
            ], 404);
        } catch (Throwable $e) {
            return response()->json([
                'error' => 'Failed to retrieve student',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified student in storage.
     *
     * @param UpdateStudentRequest $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(UpdateStudentRequest $request, $id): JsonResponse
    {
        try {
            $validated = $request->validated();

            $student = $this->studentService->updateStudent($id, $validated);
            return response()->json($student);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'error' => 'Student not found'
            ], 404);
        } catch (QueryException $e) {
            return response()->json([
                'error' => 'Database error',
                'message' => $e->getMessage()
            ], 400);
        } catch (Throwable $e) {
            return response()->json([
                'error' => 'Unexpected error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified student from storage.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function destroy($id): JsonResponse
    {
        try {
            $this->studentService->deleteStudent($id);
            return response()->json([
                'message' => 'Student deleted successfully'
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'error' => 'Student not found'
            ], 404);
        } catch (Throwable $e) {
            return response()->json([
                'error' => 'Failed to delete student',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}