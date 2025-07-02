<?php

namespace App\Modules\UserManagement\Controllers;

use App\Modules\UserManagement\Requests\UserGetRequest;
use App\Modules\UserManagement\Requests\UserCreateRequest;
use App\Modules\UserManagement\Requests\UserUpdateRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Modules\UserManagement\Services\UserService;
use Illuminate\Http\Response;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;

/**
 * @OA\Tag(
 *     name="User Management",
 *     description="API Endpoints related to User Management involving Users"
 * )
 */
class UserController extends Controller
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
     * Display a listing of users
     * 
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse 
    {
        $validated_request = UserGetRequest::validate($request);
        $users = $this->userService->getUsers(
            $validated_request['per_page'] ?? 10,
            $validated_request
        );
        return $this->sendResponse($users, 'User list retrieved successfully!');
    }

    /**
     * Store a newly created user in the database.
     * 
     * @param  Request  $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse 
    {
        try {
            $validated_request = UserCreateRequest::validate($request);
            $result = $this->userService->newUser($validated_request);
            return $this->sendCreatedResponse($result, 'User added successfully!');
        } catch (ValidationException $e) {
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
     * Update the user in storage.
     * 
     * @param  Request  $request
     * @param  int  $id
     * @return JsonResponse
     */
    public function update(Request $request, $id): JsonResponse 
    {
        try {
            $validated_request = UserUpdateRequest::validate($request, $id);
            $result = $this->userService->updateUser($id, $validated_request);
            return $this->sendResponse($result, 'User info updated successfully!');
        } catch (ModelNotFoundException $e) {
            return $this->sendError(
                'User does not exist',
                ['error' => 'User does not exist'],
                Response::HTTP_NOT_FOUND
            );
        } catch (ValidationException $e) {
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
     * Soft deletes the user in the database.
     * 
     * @param mixed $id
     * @return JsonResponse
     */
    public function destroy($id): JsonResponse 
    {
        try {
            $this->userService->deleteUser($id);
            return $this->sendResponse(null, 'User info deleted successfuly!');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->sendError(
                'User does not exist', 
                ['error' => 'User does not exist'],
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
}