<?php

namespace App\Modules\Student\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Student\Requests\StoreStudentRequest;
use App\Modules\Student\Requests\UpdateStudentRequest;
use App\Modules\Student\Requests\StudentGetRequest;
use App\Modules\Student\Services\StudentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Http\Response;
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
            $validated_request = StudentGetRequest::validate($request);
            $students = $this->studentService->getAllStudents(
                $validated_request['per_page'] ?? 10,
                $validated_request
            );
            return $this->sendResponse($students, 'Student list retrieved successfully!');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->sendValidationError($e->errors());
        } catch (Throwable $e) {
            return $this->sendError(
                'Failed to retrieve students',
                ['error' => $e->getMessage()],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
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
            return $this->sendCreatedResponse($student, 'Student added successfully!');
        } catch (ModelNotFoundException $e) {
            return $this->sendError(
                'Supervisor not found',
                ['error' => 'Supervisor not found'],
                Response::HTTP_NOT_FOUND
            );
        } catch (QueryException $e) {
            return $this->sendError(
                'Database error',
                ['error' => $e->getMessage()],
                Response::HTTP_BAD_REQUEST
            );
        } catch (Throwable $e) {
            return $this->sendError(
                'An unexpected error occurred. Please try again later.',
                ['error' => $e->getMessage()],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
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
            return $this->sendResponse($student, 'Student details retrieved successfully!');
        } catch (ModelNotFoundException $e) {
            return $this->sendError(
                'Student not found',
                ['error' => 'Student not found'],
                Response::HTTP_NOT_FOUND
            );
        } catch (Throwable $e) {
            return $this->sendError(
                'Failed to retrieve student',
                ['error' => $e->getMessage()],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
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
            return $this->sendResponse($student, 'Student info updated successfully!');
        } catch (ModelNotFoundException $e) {
            return $this->sendError(
                'Student not found',
                ['error' => 'Student not found'],
                Response::HTTP_NOT_FOUND
            );
        } catch (QueryException $e) {
            return $this->sendError(
                'Database error',
                ['error' => $e->getMessage()],
                Response::HTTP_BAD_REQUEST
            );
        } catch (Throwable $e) {
            return $this->sendError(
                'An unexpected error occurred. Please try again later.',
                ['error' => $e->getMessage()],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
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
            return $this->sendResponse(null, 'Student info deleted successfully!');
        } catch (ModelNotFoundException $e) {
            return $this->sendError(
                'Student not found',
                ['error' => 'Student not found'],
                Response::HTTP_NOT_FOUND
            );
        } catch (Throwable $e) {
            return $this->sendError(
                'Failed to delete student',
                ['error' => $e->getMessage()],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }
}