<?php

namespace App\Modules\Evaluation\Services;

use Auth;
use DB;
use App\Enums\LecturerTitle;
use App\Modules\Evaluation\Models\Chairperson;
use App\Enums\NominationStatus;
use App\Modules\Evaluation\Models\Evaluation;

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
            $chairperson = Chairperson::find($chairperson_id);

            $examiner1 = $evaluation->examiner1;
            $examiner2 = $evaluation->examiner2;
            $examiner3 = $evaluation->examiner3;

            $supervisor = $evaluation->student->supervisor;

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
}