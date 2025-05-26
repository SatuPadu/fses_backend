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
            return $this->studentService->createStudent($request->validated());
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
            return $this->studentService->importFromExcel($request);
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

    /**
     * Assign supervisors to a student.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function assignSupervisors(Request $request, int $id): JsonResponse
    {
        try {
            $data = $request->all();
            $data['student_id'] = $id;

            $validator = Validator::make($data, [
                'student_id' => ['required', 'exists:students,id'],
                'main_supervisor_id' => ['required', 'exists:lecturers,id'],
                'co_supervisor_ids' => ['nullable', 'array'],
                'co_supervisor_ids.*' => ['integer', 'exists:lecturers,id'],
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'error' => 'Validation failed.',
                    'messages' => $validator->errors(),
                ], 422);
            }

            $student = $this->studentService->assignSupervisors($id, $validator->validated());

            return response()->json([
                'message' => 'Supervisors assigned successfully.',
                'data' => $student,
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'error' => 'Failed to assign supervisors.',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}