<?php

namespace App\Modules\Evaluation\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Enums\LecturerTitle;
use App\Enums\NominationStatus;
use App\Enums\UserRole;
use App\Modules\Evaluation\Models\Evaluation;
use App\Modules\UserManagement\Models\Lecturer;
use App\Modules\UserManagement\Models\Role;
use App\Modules\Auth\Models\User;

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
     * Assign CHAIRPERSON role to user if not already assigned
     * 
     * @param int $chairpersonId
     * @return void
     */
    private function assignChairpersonRole(int $chairpersonId): void
    {
        // Find the lecturer
        $lecturer = Lecturer::find($chairpersonId);
        if (!$lecturer) {
            return;
        }

        // Find the user associated with this lecturer
        $user = User::where('staff_number', $lecturer->staff_number)->first();
        if (!$user) {
            return;
        }

        // Get CHAIRPERSON role
        $chairpersonRole = Role::findByName(UserRole::CHAIRPERSON);
        if (!$chairpersonRole) {
            return;
        }

        // Check if user already has CHAIRPERSON role
        if (!$user->hasRole(UserRole::CHAIRPERSON)) {
            // Assign CHAIRPERSON role to user
            $user->roles()->attach($chairpersonRole->id);
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

                // Assign CHAIRPERSON role to the assigned chairperson
                $this->assignChairpersonRole($assignment['chairperson_id']);
            }

            DB::commit();

            return ['message' => 'Chairpersons assigned successfully!', 'status' => 'success'];
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Get eligible chairpersons for a given evaluation, following FSES constraints.
     *
     * @param int $evaluationId
     * @return \Illuminate\Database\Eloquent\Collection
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function getEligibleChairpersons($evaluationId)
    {
        $evaluation = Evaluation::with(['student.mainSupervisor', 'examiner1', 'examiner2', 'examiner3', 'student'])->findOrFail($evaluationId);

        $mainSupervisor = $evaluation->student->mainSupervisor;
        $examiners = [
            $evaluation->examiner1,
            $evaluation->examiner2,
            $evaluation->examiner3,
        ];

        // Exclude supervisor and examiners from eligible chairpersons
        $excludeIds = array_filter([
            $mainSupervisor ? $mainSupervisor->id : null,
            $evaluation->examiner1_id,
            $evaluation->examiner2_id,
            $evaluation->examiner3_id,
        ]);

        // Only FAI lecturers
        $query = Lecturer::where('is_from_fai', true)
            ->whereNotIn('id', $excludeIds);

        // If main supervisor or any examiner is a Professor, chairperson must be a Professor
        $professorRequired = ($mainSupervisor && $mainSupervisor->title === LecturerTitle::PROFESSOR)
            || collect($examiners)->contains(fn($e) => $e && $e->title === LecturerTitle::PROFESSOR);

        if ($professorRequired) {
            $query->where('title', LecturerTitle::PROFESSOR);
        } else {
            $query->whereIn('title', [LecturerTitle::PROFESSOR, LecturerTitle::PROFESSOR_MADYA]);
        }

        // Chairperson can only chair at most 4 sessions per semester, per department
        $semester = $evaluation->semester;
        $department = $evaluation->student->department;

        $query->whereDoesntHave('chairedEvaluations', function ($q) use ($semester, $department) {
            $q->where('semester', $semester)
              ->whereHas('student', function ($sq) use ($department) {
                  $sq->where('department', $department);
              })
              ->groupBy('chairperson_id')
              ->havingRaw('COUNT(*) >= 4');
        });

        return $query->orderBy('name')->get();
    }

    /**
     * Get all eligible chairpersons for future evaluations (no evaluation context).
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function chairpersonSuggestions()
    {
        $query = Lecturer::where('is_from_fai', true)
            ->whereIn('title', [LecturerTitle::PROFESSOR, LecturerTitle::PROFESSOR_MADYA]);

        // Exclude those who have already chaired 4 or more sessions in any semester/department
        $query->whereDoesntHave('chairedEvaluations', function ($q) {
            $q->select('chairperson_id')
              ->join('students', 'student_evaluations.student_id', '=', 'students.id')
              ->groupBy('chairperson_id', 'semester', 'students.department')
              ->havingRaw('COUNT(*) >= 4');
        });

        return $query->orderBy('name')->get();
    }
}