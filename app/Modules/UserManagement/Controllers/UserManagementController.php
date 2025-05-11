<?php

namespace App\Modules\UserManagement\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\UserManagement\Models\Lecturer;
use App\Modules\UserManagement\Requests\UserRequest;
use App\Modules\UserManagement\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="User Management",
 *     description="API Endpoints related to User Management"
 * )
 */
class UserManagementController extends Controller
{
    protected $userService;

    /**
     * Inject the userService dependency.
     */
    public function __construct(UserService $userService) 
    {
        $this->userService = $userService;
    }

    /**
     * Display a listing of lecturers
     * 
     * @return JsonResponse
     */
    public function index(): JsonResponse 
    {
        $users = $this->userService->getLecturers();
        return $this->sendResponse($users, '');
    }

    /**
     * Store a newly created lecturer/user in the database.
     * 
     * @param  Request  $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse 
    {
        try {
            $validated_request = UserRequest::validate($request);
            $result = $this->userService->newLecturer($validated_request);
            return $this->sendResponse($result, 'User added successfully!');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->sendValidationError($e->errors());
        } catch (\Exception $e) {
            return $this->sendError(
                'An unexpected error occurred. Please try again later.',
                ['error' => $e->getMessage()],
                500
            );
        }
    }

    /**
     * Update the lecturer/user in storage.
     * 
     * @param  Request  $request
     * @param  int  $id
     * @return JsonResponse
     */
    public function update(Request $request, $id): JsonResponse 
    {
        // Update lecturer info
        try {
            $validated_request = UserRequest::validate($request, $id);
            $this->userService->updateLecturer($id, $validated_request);
            return $this->sendResponse(null, 'Lecturer info updated successfully!');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->sendError(
                'Lecturer does not exist',
                ['error' => 'Lecturer does not exist'],
                404
            );
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->sendValidationError($e->errors());
        } catch (\Exception $e) {
            return $this->sendError(
                'An unexpected error occurred. Please try again later.',
                ['error' => $e->getMessage()],
                500
            );
        }
    }

    /**
     * Soft deletes the lecturer/user in the database.
     * 
     * @param  int  $id
     * @return JsonResponse
     */
    public function destroy($id): JsonResponse 
    {
        try {
            $this->userService->deleteLecturer($id);
            return $this->sendResponse(null, 'Lecturer info deleted successfuly!');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->sendError(
                'Lecturer does not exist', 
                ['error' => 'Lecturer does not exist'],
                404
            );
        } catch (\Exception $e) {
            return $this->sendError(
                'An unexpected error occurred. Please try again later.',
                ['error' => $e->getMessage()],
                500
            );
        }
    }
}