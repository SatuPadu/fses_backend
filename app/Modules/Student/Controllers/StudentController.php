<?php

namespace App\Modules\Student\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Student\Requests\StoreStudentRequest;
use App\Modules\Student\Requests\ImportStudentRequest;
use App\Modules\Student\Services\StudentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Validator;
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
            $students = $this->studentService->getStudents(
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
     * Import students from uploaded Excel file.
     *
     * @param ImportStudentRequest $request
     * @return JsonResponse
     */
    public function importExcel(ImportStudentRequest $request): JsonResponse
    {
        try {
            $result = $this->studentService->importFromExcel($request->file('file'));
            return response()->json($result);
        } catch (QueryException $e) {
            return response()->json([
                'error' => 'Excel import failed - database issue',
                'message' => $e->getMessage()
            ], 400);
        } catch (Throwable $e) {
            return response()->json([
                'error' => 'Unexpected error during import',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}