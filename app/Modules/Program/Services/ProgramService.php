<?php

namespace App\Modules\Program\Services;

use App\Modules\Program\Models\Program;
use Illuminate\Support\Facades\DB;

/**
 * @service ProgramService
 * @description Provides business logic for managing academic programs.
 */
class ProgramService
{
    /**
     * Retrieve all programs.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAll()
    {
        return Program::all();
    }

    /**
     * Retrieve paginated and optionally filtered list of programs.
     *
     * @param int $numPerPage
     * @param array $request
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getPrograms(int $numPerPage, array $request)
    {
        $query = Program::query();

        if (isset($request['program_name'])) {
            $query->where('program_name', 'like', '%' . $request['program_name'] . '%');
        }

        if (isset($request['program_code'])) {
            $query->where('program_code', 'like', '%' . $request['program_code'] . '%');
        }

        if (isset($request['department'])) {
            $query->where('department', '=', $request['department']);
        }

        // Apply role-based filtering
        $user = auth()->user();
        $userRoles = $user->roles->pluck('role_name')->toArray();

        if (in_array('PGAM', $userRoles)) {
        }
        elseif (in_array('OfficeAssistant', $userRoles)) {
        }
        // Check if user is a Program Coordinator (can only see users from their department) 
        elseif (in_array('ProgramCoordinator', $userRoles)) {
            $query->where('department', $user->department);
        }
        // Check if user is a Supervisor (can only see programs of their supervised students)
        elseif (in_array('Supervisor', $userRoles)) {
            $query->whereHas('students', function ($q) use ($user) {
                $q->whereHas('mainSupervisor', function ($q2) use ($user) {
                    $q2->where('staff_number', $user->staff_number);
                });
            });
        }
        // Check if user is a Chairperson (can only see programs of students they're chairing)
        elseif (in_array('Chairperson', $userRoles)) {
            $query->whereHas('students', function ($q) use ($user) {
                $q->whereHas('evaluations', function ($evalQ) use ($user) {
                    $evalQ->whereHas('chairperson', function ($cQ) use ($user) {
                        $cQ->where('staff_number', $user->staff_number);
                    });
                });
            });
        }
        // Default: no access (empty result)
        else {
            $query->whereRaw('1 = 0'); // This will return no results
        }

        return $query->orderBy('created_at', 'desc')->paginate($numPerPage);
    }

    /**
     * Retrieve a program by ID.
     *
     * @param int $id
     * @return Program|null
     */
    public function getById($id)
    {
        return Program::findOrFail($id);
    }

    /**
     * Create a new program.
     *
     * @param array $data
     * @return Program
     */
    public function create(array $data)
    {
        try {
            DB::beginTransaction();

            $program = new Program();
            $program->program_name = $data['program_name'];
            $program->program_code = $data['program_code'];
            $program->department = $data['department'];
            $program->total_semesters = $data['total_semesters'];
            $program->evaluation_semester = $data['evaluation_semester'];
            $program->save();

            DB::commit();
            return $program;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Update an existing program.
     *
     * @param int $id
     * @param array $data
     * @return Program
     */
    public function update($id, array $data)
    {
        try {
            DB::beginTransaction();

            $program = Program::find($id);
            if (!$program) {
                throw new \Exception('Program not found', 404);
            }

            $program->program_name = $data['program_name'];
            $program->program_code = $data['program_code'];
            $program->department = $data['department'];
            $program->total_semesters = $data['total_semesters'];
            $program->evaluation_semester = $data['evaluation_semester'];
            $program->save();

            DB::commit();
            return $program;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Delete a program by ID.
     *
     * @param int $id
     * @return void
     */
    public function delete($id)
    {
        try {
            DB::beginTransaction();

            $program = Program::find($id);
            if (!$program) {
                throw new \Exception('Program not found', 404);
            }

            $program->delete();

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}