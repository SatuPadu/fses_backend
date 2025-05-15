<?php

namespace App\Modules\Student\Controllers;


use App\Http\Controllers\Controller;
use App\Modules\Student\Requests\StoreStudentRequest;
use App\Modules\Student\Requests\ImportStudentRequest;
use App\Modules\Student\Services\StudentService;
use App\Modules\Student\Imports\StudentsImport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * @OA\Tag(
 *     name="Student Management",
 *     description="API Endpoints for managing students and importing student data"
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

    public function index(Request $request): JsonResponse
    {
        try {
            $filters = $request->only('program');
            $students = $this->studentService->getAllStudents($filters);
            return $this->sendResponse($students, 'Students retrieved successfully.');
        } catch (\Exception $e) {
            return $this->sendError('Failed to retrieve students.', ['error' => $e->getMessage()], 500);
        }
    }

    public function store(StoreStudentRequest $request): JsonResponse
    {
        try {
            $student = $this->studentService->createStudent($request->validated());
            return $this->sendResponse($student, 'Student created successfully.');
        } catch (\Exception $e) {
            return $this->sendError('Failed to create student.', ['error' => $e->getMessage()], 500);
        }
    }

    
    public function importExcel(ImportStudentRequest $request): JsonResponse
    {
        try {
            $uploadedFile = $request->file('file');
            Log::info('📦 importExcel 被调用', ['file' => $uploadedFile?->getClientOriginalName(), 'valid' => $uploadedFile !== null]);

            if (!$uploadedFile) {
                return $this->sendError('No file uploaded.', ['error' => 'The uploaded file is missing.'], 400);
            }

            $this->studentService->importFromExcel(file: $uploadedFile);
            return $this->sendResponse([], 'Students imported successfully.');
        } catch (\Exception $e) {
            return $this->sendError('Excel import failed.', ['error' => $e->getMessage()], 500);
        }
    }
}