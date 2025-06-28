<?php

namespace App\Modules\Evaluation\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Enums\LecturerTitle;
use App\Enums\NominationStatus;
use App\Modules\Evaluation\Models\Evaluation;
use App\Modules\UserManagement\Models\Lecturer;

/**
 * Service class for handling business logic related to the Program Coordinator role.
 */
class AssignmentService
{
    /**
     * Validate chairperson based on eligibility rules.
     * 
     * @param array $request_list
     * @throws \Exception
     * @return void
     */
    private function validateChairpersonEligibility(array $request_list)
    {
        foreach ($request_list as $request) {
            $chairperson_id = $request['chairperson_id'];

            $evaluation = Evaluation::find($request['evaluation_id']);
            $student = $evaluation->student;
            $chairperson = Lecturer::find($chairperson_id);

            $examiner1 = $evaluation->examiner1;
            $examiner2 = $evaluation->examiner2;
            $examiner3 = $evaluation->examiner3;

            $supervisor = $student->mainSupervisor;

            $sessions = Evaluation::join('students', 'student_evaluations.student_id', '=', 'students.id')
            ->where([
                ['student_evaluations.chairperson_id', '=', $chairperson_id],
                ['student_evaluations.semester', '=', $evaluation->semester],
                ['students.department', '=', $student->department]
            ])->count();
            
            // Check if current sessions already reach max
            if ($sessions >= 4) {
                throw new \Exception('Num of sessions reached max!');
            }

            // Validate chairperson title
            $titles = [
                $supervisor->title,
                $examiner1->title ?? null,
                $examiner2->title ?? null,
                $examiner3->title ?? null
            ];
            
            if (in_array(LecturerTitle::PROFESSOR, $titles)) {
                if ($chairperson->title != LecturerTitle::PROFESSOR) {
                    throw new \Exception('Chairperson must be Prof!');
                }
            } else if ($chairperson->title == LecturerTitle::DR) {
                throw new \Exception('Chairperson must be at least Assoc Prof!');
            }

            // Validate chairperson to not be the supervisor/examiner of the same student
            $ids = [
                $supervisor->id,
                $examiner1->id ?? null,
                $examiner2->id ?? null,
                $examiner3->id ?? null
            ];

            if (in_array($chairperson->id, $ids)) {
                throw new \Exception('Chairperson cannot simultaneously be a supervisor/examiner of the same student!');
            }
        }
    }

    /**
     * Update an existing evaluation record.
     * 
     * @param array $assignment_list
     * @return array{message: string, status: string}
     */
    public function assign(array $assignment_list): array
    {
        $this->validateChairpersonEligibility($assignment_list);

        try {
            DB::beginTransaction();
            foreach ($assignment_list as $assignment) {
                $evaluation = Evaluation::find($assignment['evaluation_id']);

                $evaluation->semester = $assignment['semester'] ?? $evaluation->semester;
                $evaluation->academic_year = $assignment['academic_year'] ?? $evaluation->academic_year;
                $evaluation->chairperson_id = $assignment['chairperson_id'];
                $evaluation->is_auto_assigned = $assignment['is_auto_assigned'];
                $evaluation->save();
            }

            DB::commit();

            return ['message' => 'Chairpersons assigned successfully!', 'status' => 'success'];
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Lock an evaluation nomination with user and time context.
     * 
     * @param int $id
     * @throws \Exception
     * @return Evaluation|Evaluation[]|\Illuminate\Database\Eloquent\Collection|\Illuminate\Database\Eloquent\Model
     */
    public function lock(int $id): Evaluation
    {
        try {
            DB::beginTransaction();

            $evaluation = Evaluation::findOrFail($id);
            $evaluation->locked_by = Auth::id();
            $evaluation->locked_at = now();
            $evaluation->nomination_status = NominationStatus::LOCKED;
            $evaluation->save();

            DB::commit();
            return $evaluation;
        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception('Nomination lock unsuccessful!');
        }
    }

    /**
     * Retrieve paginated and optionally filtered list of assignments.
     *
     * @param int $numPerPage
     * @param array $filters
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getAssignments(int $numPerPage, array $filters)
    {
        $query = Evaluation::with(['student', 'chairperson'])
            ->whereNotNull('chairperson_id');

        // Apply filters
        if (isset($filters['student_id'])) {
            $query->where('student_id', $filters['student_id']);
        }

        if (isset($filters['chairperson_id'])) {
            $query->where('chairperson_id', $filters['chairperson_id']);
        }

        if (isset($filters['semester'])) {
            $query->where('semester', $filters['semester']);
        }

        if (isset($filters['academic_year'])) {
            $query->where('academic_year', $filters['academic_year']);
        }

        if (isset($filters['is_auto_assigned'])) {
            $query->where('is_auto_assigned', $filters['is_auto_assigned']);
        }

        // Apply role-based filtering
        $user = auth()->user();
        $userRoles = $user->roles->pluck('role_name')->toArray();

        if (in_array('PGAM', $userRoles)) {
        }
        elseif (in_array('OfficeAssistant', $userRoles)) {
        }
        // Check if user is a Program Coordinator (can only see assignments from their department) 
        elseif (in_array('ProgramCoordinator', $userRoles)) {
            $query->whereHas('student', function ($q) use ($user) {
                $q->where('department', $user->department);
            });
        }
        // Check if user is a Research Supervisor (can only see assignments of their students)
        elseif (in_array('ResearchSupervisor', $userRoles)) {
            $query->whereHas('student', function ($q) use ($user) {
                $q->whereHas('mainSupervisor', function ($q2) use ($user) {
                    $q2->where('staff_number', $user->staff_number);
                });
            });
        }
        // Check if user is a Chairperson (can only see students they're chairing)
        elseif (in_array('Chairperson', $userRoles)) {
            $query->whereHas('chairperson', function ($cQ) use ($user) {
                $cQ->where('staff_number', $user->staff_number);
            });
        }
        // Default: no access (empty result)
        else {
            $query->whereRaw('1 = 0'); // This will return no results
        }

        return $query->orderBy('created_at', 'desc')->paginate($numPerPage);
    }
}