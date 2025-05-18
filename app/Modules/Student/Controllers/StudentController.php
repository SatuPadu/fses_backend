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
use Throwable;

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
            return $this->studentService->getAllStudents($request);
        } catch (Throwable $e) {
            return response()->json([
                'error' => 'Failed to retrieve students',
                'message' => $e->getMessage()
            ], 500);
        }
    }

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
}