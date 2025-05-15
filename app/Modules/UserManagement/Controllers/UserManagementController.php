<?php

namespace App\Modules\UserManagement\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Modules\UserManagement\Models\Lecturer;
use App\Modules\UserManagement\Services\UserService;
use App\Modules\UserManagement\Requests\UserCreateRequest;
use App\Modules\UserManagement\Requests\UserUpdateRequest;

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
            $validated_request = UserCreateRequest::validate($request);
            $result = $this->userService->newLecturer($validated_request);
            return $this->sendResponse($result, 'User added successfully!');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->sendValidationError($e->errors());
        } catch (\Exception $e) {
            return $this->sendError(
                'An unexpected error occurred. Please try again later.',
                ['error' => $e->getMessage()],
                $e->getCode()
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
            $validated_request = UserUpdateRequest::validate($request, $id);
            $result = $this->userService->updateLecturer($id, $validated_request);
            return $this->sendResponse($result, 'Lecturer info updated successfully!');
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
                $e->getCode()
            );
        }
    }

    /**
     * Soft deletes the lecturer in the database.
     * 
     * @param  int  $id
     * @return JsonResponse
     */
    public function destroyLecturer($id): JsonResponse 
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

    /**
     * Soft deletes the user in the database.
     * 
     * @param mixed $id
     * @return JsonResponse
     */
    public function destroyUser($id): JsonResponse 
    {
        try {
            $this->userService->deleteUser($id);
            return $this->sendResponse(null, 'User info deleted successfuly!');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->sendError(
                'User does not exist', 
                ['error' => 'User does not exist'],
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