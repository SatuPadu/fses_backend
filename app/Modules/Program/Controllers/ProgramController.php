<?php

namespace App\Modules\Program\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Modules\Program\Models\Program;
use App\Modules\Program\Requests\StoreProgramRequest;
use App\Modules\Program\Requests\UpdateProgramRequest;
use App\Modules\Program\Services\ProgramService;

/**
 * @module Program
 * @controller ProgramController
 * @description Handles CRUD operations for academic programs.
 */
class ProgramController extends Controller
{
    protected $programService;

    /**
     * ProgramController constructor.
     *
     * @param ProgramService $programService
     */
    public function __construct(ProgramService $programService)
    {
        $this->programService = $programService;
    }

    /**
     * @route GET /api/programs
     * @description List all programs
     */
    public function index()
    {
        return response()->json($this->programService->getAll());
    }

    /**
     * @route POST /api/programs
     * @description Store a new program
     */
    public function store(StoreProgramRequest $request)
    {
        $program = $this->programService->create($request->validated());
        return response()->json($program, 201);
    }

    /**
     * @route GET /api/programs/{id}
     * @description Show a specific program by ID
     */
    public function show($id)
    {
        $program = $this->programService->getById($id);
        return response()->json($program);
    }

    /**
     * @route PUT /api/programs/{id}
     * @description Update a specific program by ID
     */
    public function update(UpdateProgramRequest $request, $id)
    {
        $program = $this->programService->update($id, $request->validated());
        return response()->json($program);
    }

    /**
     * @route DELETE /api/programs/{id}
     * @description Delete a specific program by ID
     */
    public function destroy($id)
    {
        $this->programService->delete($id);
        return response()->json(['message' => 'Program deleted successfully.']);
    }
}