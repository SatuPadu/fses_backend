<?php

namespace App\Modules\Auth\Controllers;

use App\Enums\Department;
use App\Enums\LecturerTitle;
use App\Enums\UserRole;
use App\Enums\NominationStatus;
use App\Enums\EvaluationType;
use App\Enums\ProgramName;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;

/**
 * @OA\Tag(
 *     name="Enums",
 *     description="API Endpoints for getting enum lists"
 * )
 */
class EnumController extends Controller
{
    /**
     * Get all enum lists for the frontend
     * 
     * @return JsonResponse
     * 
     * @OA\Get(
     *     path="/api/enums",
     *     summary="Get all enum lists",
     *     description="Returns all enum lists including departments, titles, roles, nomination status, and evaluation types",
     *     tags={"Enums"},
     *     @OA\Response(
     *         response=200,
     *         description="Success",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="departments", type="array", @OA\Items(type="string")),
     *                 @OA\Property(property="titles", type="array", @OA\Items(type="string")),
     *                 @OA\Property(property="roles", type="array", @OA\Items(type="string")),
     *                 @OA\Property(property="nominationStatus", type="array", @OA\Items(type="string")),
     *                 @OA\Property(property="evaluationTypes", type="array", @OA\Items(type="string"))
     *             ),
     *             @OA\Property(property="message", type="string", example="Enum lists retrieved successfully!")
     *         )
     *     )
     * )
     */
    public function index(): JsonResponse
    {
        $enums = [
            'departments' => Department::forSelect(),
            'titles' => LecturerTitle::forSelect(),
            'roles' => UserRole::forSelect(),
            'nominationStatus' => NominationStatus::forSelect(),
            'evaluationTypes' => EvaluationType::forSelect(),
            'programNames' => ProgramName::forSelect(),
        ];

        return $this->sendResponse($enums, 'Enum lists retrieved successfully!');
    }
} 