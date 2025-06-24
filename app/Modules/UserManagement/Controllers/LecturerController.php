<?php

namespace App\Modules\UserManagement\Controllers;

use App\Modules\UserManagement\Requests\LecturerCreateRequest;
use App\Modules\UserManagement\Requests\LecturerGetRequest;
use App\Modules\UserManagement\Requests\LecturerUpdateRequest;
use App\Modules\UserManagement\Services\LecturerService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use Illuminate\Http\Response;

/**
 * @OA\Tag(
 *     name="User Management",
 *     description="API Endpoints related to User Management involving Lecturers"
 * )
 */
class LecturerController extends Controller
{
    protected $lecturerService;

    /**
     * Inject the lecturerService dependency.
     */
    public function __construct(LecturerService $lecturerService) 
    {
        $this->lecturerService = $lecturerService;
    }

    /**
     * Display a listing of lecturers
     * 
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse 
    {
        $validated_request = LecturerGetRequest::validate($request);
        
        // Check if FAI lecturers only are requested
        if (isset($validated_request['fai']) && $validated_request['fai'] === 'true') {
            $lecturers = $this->lecturerService->getFAILecturers(
                $validated_request['per_page'] ?? 10,
                $validated_request
            );
        } else {
        $lecturers = $this->lecturerService->getLecturers(
            $validated_request['per_page'] ?? 10,
            $validated_request
        );
        }
        
        return $this->sendResponse($lecturers, 'Lecturer list retrieved successfully!');
    }

    /**
     * Store a newly created lecturer in the database.
     * 
     * @param  Request  $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse 
    {
        try {
            $validated_request = LecturerCreateRequest::validate($request);
            $result = $this->lecturerService->newLecturer($validated_request);
            return $this->sendCreatedResponse($result, 'Lecturer added successfully!');
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

    /**
     * Update the lecturer in storage.
     * 
     * @param  Request  $request
     * @param  int  $id
     * @return JsonResponse
     */
    public function update(Request $request, $id): JsonResponse 
    {
        try {
            $validated_request = LecturerUpdateRequest::validate($request, $id);
            $result = $this->lecturerService->updateLecturer($id, $validated_request);
            return $this->sendResponse($result, 'Lecturer info updated successfully!');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->sendError(
                'Lecturer does not exist',
                ['error' => 'Lecturer does not exist'],
                Response::HTTP_NOT_FOUND
            );
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

    /**
     * Soft deletes the lecturer in the database.
     * 
     * @param  int  $id
     * @return JsonResponse
     */
    public function destroy($id): JsonResponse 
    {
        try {
            $this->lecturerService->deleteLecturer($id);
            return $this->sendResponse(null, 'Lecturer info deleted successfuly!');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->sendError(
                'Lecturer does not exist', 
                ['error' => 'Lecturer does not exist'],
                Response::HTTP_NOT_FOUND
            );
        } catch (\Exception $e) {
            return $this->sendError(
                'An unexpected error occurred. Please try again later.',
                ['error' => $e->getMessage()],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Get lecturer details
     * 
     * @param int $id
     * @param \Illuminate\Http\Request $request
     * @return JsonResponse
     */
    public function lecturerDetail(int $id, Request $request): JsonResponse
    {
        try {
            $semester = $request->get('semester');
            $academicYear = $request->get('academic_year');
            
            $lecturerDetails = $this->lecturerService->getLecturerDetails($id, $semester, $academicYear);

            return $this->sendResponse($lecturerDetails, 'Lecturer details with workload statistics retrieved successfully!');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->sendError('Lecturer not found.', [], Response::HTTP_NOT_FOUND);
        } catch (\Exception $e) {
            return $this->sendError(
                'An unexpected error occurred. Please try again later.',
                ['error' => $e->getMessage()],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }
}