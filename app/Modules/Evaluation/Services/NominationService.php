<?php

namespace App\Modules\Evaluation\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Exception;
use App\Enums\LecturerTitle;
use App\Enums\NominationStatus;
use App\Enums\UserRole;
use App\Mail\EvaluationPostponedMail;
use App\Modules\Student\Models\Student;
use App\Modules\UserManagement\Models\Lecturer;
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
            $examiner1 = Lecturer::find($request['examiner1_id']);
            $supervisor = null;
            if (isset($request['evaluation_id'])) {
                $supervisor = Evaluation::find($request['evaluation_id'])->student->mainSupervisor;
            }
            else if (isset($request['student_id'])) {
                $supervisor = Student::find($request['student_id'])->mainSupervisor;
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
            $examiner2 = Lecturer::find($request['examiner2_id']);

            if ($examiner2->id == $supervisor->id) {
                throw new Exception('Examiner 2 must not be the main supervisor of the student!');
            }
        }

        // Examiner 3 validation
        if (isset($request['examiner3_id'])) {
            $examiner3 = Lecturer::find($request['examiner3_id']);

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
            $evaluation = Evaluation::with(['student.program', 'student.mainSupervisor', 'student.coSupervisors.lecturer', 'examiner1', 'examiner2', 'examiner3', 'chairperson'])->findOrFail($request['evaluation_id']);
            
            $evaluation->nomination_status = NominationStatus::POSTPONED;
            $evaluation->is_postponed = true;
            $evaluation->postponement_reason = $request['reason'];
            $evaluation->postponed_to = $request['postponed_to'];
            $evaluation->save();

            // Send email notifications to all committee members (excluding office assistants)
            $this->sendPostponementNotifications($evaluation, $request['reason'], $request['postponed_to']);

            return $evaluation;
        });
    }

    /**
     * Send email notifications to evaluation committee members about postponement.
     *
     * @param Evaluation $evaluation
     * @param string $reason
     * @param string $postponedTo
     * @return void
     */
    private function sendPostponementNotifications(Evaluation $evaluation, string $reason, string $postponedTo): void
    {
        $recipients = collect();

        // Get all committee members
        $committeeMembers = [
            $evaluation->examiner1,
            $evaluation->examiner2,
            $evaluation->examiner3,
            $evaluation->chairperson,
        ];

        // Filter out null values and get their user accounts
        foreach ($committeeMembers as $member) {
            if ($member) {
                $user = \App\Modules\Auth\Models\User::where('staff_number', $member->staff_number)->first();
                if ($user) {
                    $recipients->push($user);
                }
            }
        }

        // Add the student's main supervisor if not already in the committee
        if ($evaluation->student->mainSupervisor) {
            $supervisorUser = \App\Modules\Auth\Models\User::where('staff_number', $evaluation->student->mainSupervisor->staff_number)->first();
            if ($supervisorUser && !$recipients->contains('id', $supervisorUser->id)) {
                $recipients->push($supervisorUser);
            }
        }

        // Add co-supervisors if any
        $coSupervisors = $evaluation->student->coSupervisors;
        foreach ($coSupervisors as $coSupervisor) {
            $coSupervisorUser = \App\Modules\Auth\Models\User::where('staff_number', $coSupervisor->staff_number)->first();
            if ($coSupervisorUser && !$recipients->contains('id', $coSupervisorUser->id)) {
                $recipients->push($coSupervisorUser);
            }
        }

        // Filter out users with Office Assistant role
        $recipients = $recipients->filter(function ($user) {
            return !$user->hasRole(UserRole::OFFICE_ASSISTANT);
        });

        // Send email to each recipient
        foreach ($recipients as $recipient) {
            try {
                Mail::to($recipient->email)->send(new EvaluationPostponedMail($evaluation, $reason, $postponedTo));
            } catch (Exception $e) {
                // Log the error but don't fail the entire operation
                Log::error('Failed to send postponement email to ' . $recipient->email . ': ' . $e->getMessage());
            }
        }
    }

    /**
     * Lock multiple nominations to prevent further modifications.
     *
     * @param array $evaluationIds
     * @return int
     */
    public function lockNominations(array $evaluationIds): int
    {
        return DB::transaction(function () use ($evaluationIds) {
            $lockedCount = 0;
            
            foreach ($evaluationIds as $evaluationId) {
                $evaluation = Evaluation::find($evaluationId);
                
                if ($evaluation && $evaluation->nomination_status !== NominationStatus::LOCKED) {
                    $evaluation->nomination_status = NominationStatus::LOCKED;
                    $evaluation->locked_by = Auth::id();
                    $evaluation->locked_at = now();
                    $evaluation->save();
                    $lockedCount++;
                }
            }
            
            return $lockedCount;
        });
    }

    /**
     * Retrieve paginated and optionally filtered list of nominations.
     *
     * @param int $numPerPage
     * @param array $filters
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getNominations(int $numPerPage, array $filters)
    {
        $query = Evaluation::with(['student.program', 'student.mainSupervisor', 'student.coSupervisors.lecturer', 'examiner1', 'examiner2', 'examiner3', 'chairperson']);

        // Apply filters
        if (isset($filters['student_id'])) {
            $query->where('student_id', $filters['student_id']);
        }

        if (isset($filters['nomination_status'])) {
            $query->where('nomination_status', $filters['nomination_status']);
        }

        if (isset($filters['is_postponed'])) {
            $query->where('is_postponed', $filters['is_postponed']);
        }

        if (isset($filters['semester'])) {
            $query->where('semester', $filters['semester']);
        }

        if (isset($filters['academic_year'])) {
            $query->where('academic_year', $filters['academic_year']);
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
            $query->whereHas('student', function ($q) use ($user) {
                $q->where('department', $user->department);
            });
        }
        // Check if user is a Supervisor (can only see their supervised students)
        elseif (in_array('Supervisor', $userRoles)) {
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

    /**
     * Get unique academic years from evaluations table.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getUniqueAcademicYears()
    {
        return Evaluation::select('academic_year')
            ->distinct()
            ->whereNotNull('academic_year')
            ->orderBy('academic_year', 'desc')
            ->pluck('academic_year');
    }
}