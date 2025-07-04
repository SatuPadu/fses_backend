<?php

namespace App\Modules\Program\Services;

use App\Modules\Program\Models\Program;
use Illuminate\Support\Facades\DB;
use App\Enums\ProgramName;

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
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator|\Illuminate\Database\Eloquent\Collection
     */
    public function getPrograms(int $numPerPage, array $request)
    {
        $query = Program::query();

        // Apply basic filters first
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

        // Check if user has any of the relevant roles
        $hasPGAM = in_array('PGAM', $userRoles);
        $hasOfficeAssistant = in_array('OfficeAssistant', $userRoles);
        $hasProgramCoordinator = in_array('ProgramCoordinator', $userRoles);
        $hasResearchSupervisor = in_array('ResearchSupervisor', $userRoles);
        $hasChairperson = in_array('Chairperson', $userRoles);

        if ($hasPGAM || $hasOfficeAssistant) {
            // PGAM and Office Assistant can see all programs, but department filter from request should still apply
        }
        else {
            // For other roles, combine access permissions
            $query->where(function ($q) use ($user, $hasProgramCoordinator, $hasResearchSupervisor, $hasChairperson) {
                $hasAccess = false;

                // Check if user is a Program Coordinator (can see programs from their department)
                if ($hasProgramCoordinator) {
                    $q->orWhere('department', $user->department);
                    $hasAccess = true;
                }

                // Check if user is a Research Supervisor (can see programs of their supervised students)
                if ($hasResearchSupervisor) {
                    $q->orWhereHas('students', function ($studentQ) use ($user) {
                        $studentQ->whereHas('mainSupervisor', function ($supervisorQ) use ($user) {
                            $supervisorQ->where('staff_number', $user->staff_number);
                        });
                    });
                    $hasAccess = true;
                }

                // Check if user is a Chairperson (can see programs of students they're chairing)
                if ($hasChairperson) {
                    $q->orWhereHas('students', function ($studentQ) use ($user) {
                        $studentQ->whereHas('evaluations', function ($evalQ) use ($user) {
                            $evalQ->whereHas('chairperson', function ($chairQ) use ($user) {
                                $chairQ->where('staff_number', $user->staff_number);
                            });
                        });
                    });
                    $hasAccess = true;
                }

                // If user has no relevant roles, return no results
                if (!$hasAccess) {
                    $q->whereRaw('1 = 0');
                }
            });
        }

        $query->orderBy('created_at', 'desc');

        // Check if all=true parameter is present or if numPerPage is -1 (return all items)
        if ((isset($request['all']) && $request['all'] === 'true') || $numPerPage <= 0) {
            $items = $query->get();
            $count = $items->count();
            return [
                'items' => $items,
                'pagination' => [
                    'total' => $count,
                    'per_page' => $count,
                    'current_page' => 1,
                    'last_page' => 1,
                    'from' => $count > 0 ? 1 : null,
                    'to' => $count > 0 ? $count : null,
                ]
            ];
        }

        // Ensure numPerPage is at least 1 for pagination
        $perPage = max(1, $numPerPage);
        return $query->paginate($perPage);
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
        $programName = trim($data['program_name']);
        
        // Map full program names to enum values for validation
        $programNameMapping = [
            'Doctor of Philosophy' => ProgramName::PHD,
            'Master of Philosophy' => ProgramName::MPHIL,
            'Doctor of Software Engineering' => ProgramName::DSE
        ];
        
        $enumProgramName = $programNameMapping[$programName] ?? $programName;
        
        if (!ProgramName::isValid($enumProgramName)) {
            throw new \Exception('Invalid program name: ' . $data['program_name'] . '. Valid options are: Doctor of Philosophy, Master of Philosophy, Doctor of Software Engineering, or the short forms: ' . implode(', ', ProgramName::all()));
        }
        
        try {
            DB::beginTransaction();

            $program = new Program();
            $program->program_name = $programName;
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
        $programName = trim($data['program_name']);
        
        // Map full program names to enum values for validation
        $programNameMapping = [
            'Doctor of Philosophy' => ProgramName::PHD,
            'Master of Philosophy' => ProgramName::MPHIL,
            'Doctor of Software Engineering' => ProgramName::DSE
        ];
        
        $enumProgramName = $programNameMapping[$programName] ?? $programName;
        
        if (!ProgramName::isValid($enumProgramName)) {
            throw new \Exception('Invalid program name: ' . $data['program_name'] . '. Valid options are: Doctor of Philosophy, Master of Philosophy, Doctor of Software Engineering, or the short forms: ' . implode(', ', ProgramName::all()));
        }
        
        try {
            DB::beginTransaction();

            $program = Program::find($id);
            if (!$program) {
                throw new \Exception('Program not found', 404);
            }

            $program->program_name = $programName; // Store the full program name
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

            // Soft delete all students and their evaluations
            foreach ($program->students as $student) {
                // Soft delete all evaluations for this student
                foreach ($student->evaluations as $evaluation) {
                    $evaluation->delete();
                }
                $student->delete();
            }

            $program->delete();

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}