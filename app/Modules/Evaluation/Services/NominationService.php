<?php

namespace App\Modules\Evaluation\Services;

use Auth;
use Exception;
use App\Enums\LecturerTitle;
use App\Enums\NominationStatus;
use Illuminate\Support\Facades\DB;
use App\Modules\Evaluation\Models\Student;
use App\Modules\Nomination\Models\Examiner;
use App\Modules\Evaluation\Models\Evaluation;

/**
 * Service class for handling business logic related to the Supervisor role.
 */
class NominationService
{
    private function validateNominees(array $request)
    {
        // Examiner 1 validation
        if (isset($request['examiner1_id'])) {
            $examiner1 = Examiner::find($request['examiner1_id']);
            $supervisor = null;
            if (isset($request['evaluation_id'])) {
                $supervisor = Evaluation::find($request['evaluation_id'])->student->supervisor;
            }
            else if (isset($request['student_id'])) {
                $supervisor = Student::find($request['student_id'])->supervisor;
            }

            if ($examiner1->id == $supervisor->id) {
                throw new Exception('Examiner 1 must not be the main supervisor of the student!');
            }
            else if (!$examiner1->is_from_fai) {
                throw new Exception('Examiner 1 is not from FAI!');
            }
            else if ($supervisor->title == LecturerTitle::PROFESSOR) {
                if ($examiner1->title != LecturerTitle::PROFESSOR) {
                    throw new Exception('Examiner 1 must be a Prof as main supervisor is a Prof!');
                }
            }
            else {
                if ($examiner1->title == LecturerTitle::DR) {
                    throw new Exception('Examiner 1 must be at least an Assoc Prof!');
                }
            }
        }

        // Examiner 2 validation
        if (isset($request['examiner2_id'])) {
            $examiner2 = Examiner::find($request['examiner2_id']);

            if ($examiner2->id == $supervisor->id) {
                throw new Exception('Examiner 2 must not be the main supervisor of the student!');
            }
            else if ($examiner2->title == LecturerTitle::DR) {
                throw new Exception('Examiner 2 must be at least an Assoc Prof!');
            }
        }

        // Examiner 3 validation
        if (isset($request['examiner3_id'])) {
            $examiner3 = Examiner::find($request['examiner3_id']);

            if ($examiner3->id == $supervisor->id) {
                throw new Exception('Examiner 3 must not be the main supervisor of the student!');
            }
            else if (!$examiner3->is_from_fai) {
                throw new Exception('Examiner 3 is not from FAI!');
            }
        }
    }

    /**
     * Store a new evaluation record.
     *
     * @param array $data
     * @return Evaluation
     */
    public function createNomination(array $request): Evaluation
    {
        $this->validateNominees($request);
        $status = NominationStatus::PENDING;
        if (isset($request['examiner1_id']) && isset($request['examiner2_id']) && isset($request['examiner3_id'])) {
            $status = NominationStatus::NOMINATED;
        }

        $evaluation = Evaluation::create([
            'student_id' => $request['student_id'],
            'semester' => $request['semester'],
            'academic_year' => $request['academic_year'],
            'examiner1_id' => $request['examiner1_id'] ?? null,
            'examiner2_id' => $request['examiner2_id'] ?? null,
            'examiner3_id' => $request['examiner3_id'] ?? null,
            'nomination_status' => $status,
            'nominated_by' => Auth::id(),
            'nominated_at' => now(),
        ]);

        return $evaluation;
    }

    /**
     * Update an existing evaluation record.
     *
     * @param int $id
     * @param array $data
     * @return Evaluation
     */
    public function updateNomination(array $request): Evaluation
    {
        $this->validateNominees($request);
        $evaluation = Evaluation::find($request['evaluation_id']);

        if ($evaluation->nomination_status == NominationStatus::LOCKED) {
            throw new Exception('Examiner Nominations have been locked! No further modifications are allowed!');
        }

        $evaluation->semester = $request['semester'] ?? $evaluation->semester;
        $evaluation->academic_year = $request['academic_year'] ?? $evaluation->academic_year;
        $evaluation->examiner1_id = $request['examiner1_id'] ?? null;
        $evaluation->examiner2_id = $request['examiner2_id'] ?? null;
        $evaluation->examiner3_id = $request['examiner3_id'] ?? null;
        $evaluation->nominated_by = Auth::id();
        $evaluation->nominated_at = now();
        $evaluation->save();

        $evaluation->refresh();

        if (isset($evaluation->examiner1_id) && isset($evaluation->examiner2_id) && isset($evaluation->examiner3_id)) {
            $evaluation->nomination_status = NominationStatus::NOMINATED;
            $evaluation->save();
        }
        else {
            $evaluation->nomination_status = NominationStatus::PENDING;
            $evaluation->save();
        }

        return $evaluation;
    }

    /**
     * Postpone an evaluation by setting its status and timestamp.
     *
     * @param int $id
     * @return Evaluation
     */
    public function postpone(array $request): Evaluation
    {
        return DB::transaction(function () use ($request) {
            $evaluation = Evaluation::findOrFail($request['evaluation_id']);
            $evaluation->nomination_status = NominationStatus::POSTPONED;
            $evaluation->is_postponed = true;
            $evaluation->postponement_reason = $request['reason'];
            $evaluation->postponed_to = $request['postponed_to'];
            $evaluation->save();
            return $evaluation;
        });
    }

    // /**
    //  * Retrieve students eligible for evaluation based on RS supervision.
    //  *
    //  * @param int $rsId
    //  * @return array
    //  */
    // public function getEligibleStudents(int $rsId): array
    // {
    //     return Student::where('main_supervisor_id', $rsId)
    //         ->where('is_postponed', false)
    //         ->get()
    //         ->toArray();
    // }
}