<?php

namespace App\Modules\Dashboard\Services;

use App\Modules\Student\Models\Student;
use App\Modules\Evaluation\Models\Evaluation;
use App\Modules\UserManagement\Models\Lecturer;
use App\Models\Log;
use App\Enums\NominationStatus;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    public function getDashboardData(): array
    {
        $user = Auth::user();
        $userRoles = $user->roles->pluck('role_name')->toArray();

        $data = [
            'system_overview' => $this->getSystemOverview(),
            'recent_activity' => $this->getRecentActivity(5),
        ];

        if (in_array('OfficeAssistant', $userRoles)) {
            $data['office_assistant'] = $this->getOfficeAssistantData();
        }

        if (in_array('ResearchSupervisor', $userRoles)) {
            $data['research_supervisor'] = $this->getResearchSupervisorData();
        }

        if (in_array('ProgramCoordinator', $userRoles)) {
            $data['program_coordinator'] = $this->getProgramCoordinatorData();
        }

        if (in_array('PGAM', $userRoles)) {
            $data['pgam'] = $this->getPGAMData();
        }

        return $data;
    }

    public function getOfficeAssistantData(): array
    {
        return [
            'student_management' => [
                'total_students' => Student::count(),
                'by_program' => $this->getStudentsByProgram(),
                'by_department' => $this->getStudentsByDepartment(),
                'by_evaluation_type' => $this->getStudentsByEvaluationType(),
                'by_semester' => $this->getStudentsBySemester(),
            ],
            'quick_actions' => [
                'add_student' => true,
                'import_student_data' => true,
                'export_student_lists' => true,
            ]
        ];
    }

    public function getResearchSupervisorData(): array
    {
        $user = Auth::user();
        $lecturer = $user->lecturer;

        if (!$lecturer) {
            return [
                'my_students' => [
                    'total_students' => 0,
                    'eligible_for_evaluation' => 0,
                    'pending_nominations' => 0,
                    'completed_nominations' => 0,
                    'postponed_evaluations' => 0,
                ],
                'recent_nominations' => [],
                'pending_nominations_list' => [],
                'locked_nominations_list' => [],
            ];
        }

        $myStudents = Student::where('main_supervisor_id', $lecturer->id)->with(['evaluations', 'program'])->get();

        return [
            'my_students' => [
                'total_students' => $myStudents->count(),
                'eligible_for_evaluation' => $myStudents->where('current_semester', '>=', 2)->count(),
                'pending_nominations' => $myStudents->filter(function ($student) {
                    return $student->evaluations->where('nomination_status', NominationStatus::PENDING)->count() > 0;
                })->count(),
                'completed_nominations' => $myStudents->filter(function ($student) {
                    return $student->evaluations->where('nomination_status', NominationStatus::NOMINATED)->count() > 0;
                })->count(),
                'postponed_evaluations' => $myStudents->filter(function ($student) {
                    return $student->evaluations->where('nomination_status', NominationStatus::POSTPONED)->count() > 0;
                })->count(),
            ],
            'recent_nominations' => $this->getRecentNominations($lecturer->id),
            'pending_nominations_list' => $this->getPendingNominationsList($lecturer->id),
            'locked_nominations_list' => $this->getLockedNominationsList($lecturer->id),
        ];
    }

    public function getProgramCoordinatorData(): array
    {
        $user = Auth::user();
        $department = $user->department;

        return [
            'department_overview' => [
                'total_students' => Student::where('department', $department)->count(),
                'by_program' => $this->getStudentsByProgramInDepartment($department),
                'by_evaluation_status' => $this->getStudentsByEvaluationStatusInDepartment($department),
            ],
            'evaluation_management' => [
                'awaiting_chairperson_assignment' => $this->getStudentsAwaitingChairpersonAssignment($department),
                'completed_assignments' => $this->getStudentsWithCompletedAssignments($department),
                'postponed_evaluations' => $this->getStudentsWithPostponedEvaluations($department),
                'locked_nominations_count' => $this->getLockedNominationsCount($department),
            ],
            'workload_distribution' => [
                'examiners' => $this->getExaminersWorkload($department),
                'chairpersons' => $this->getChairpersonsWorkload($department),
            ]
        ];
    }

    public function getPGAMData(): array
    {
        return [
            'faculty_wide_overview' => [
                'total_students' => Student::count(),
                'by_program' => $this->getStudentsByProgram(),
                'by_evaluation_type' => $this->getStudentsByEvaluationType(),
                'by_nomination_status' => $this->getStudentsByNominationStatus(),
            ],
            'department_comparison' => [
                'student_counts' => $this->getStudentCountsByDepartment(),
                'evaluation_progress' => $this->getEvaluationProgressByDepartment(),
                'workload_distribution' => $this->getWorkloadDistributionByDepartment(),
            ],
            'system_statistics' => [
                'total_lecturers' => Lecturer::count(),
                'total_evaluations_this_semester' => $this->getTotalEvaluationsThisSemester(),
                'average_workload_per_examiner' => $this->getAverageWorkloadPerExaminer(),
                'average_workload_per_chairperson' => $this->getAverageWorkloadPerChairperson(),
                'system_usage_statistics' => $this->getSystemUsageStatistics(),
            ]
        ];
    }

    public function getSystemOverview(): array
    {
        $currentEvaluation = Evaluation::latest()->first();
        $currentSemester = $currentEvaluation ? $currentEvaluation->semester : null;
        $currentAcademicYear = $currentEvaluation ? $currentEvaluation->academic_year : null;

        return [
            'total_students' => Student::count(),
            'total_evaluations' => Evaluation::count(),
            'current_semester' => $currentSemester,
            'current_academic_year' => $currentAcademicYear,
            'evaluations_this_semester' => $currentSemester && $currentAcademicYear 
                ? Evaluation::where('semester', $currentSemester)
                    ->where('academic_year', $currentAcademicYear)
                    ->count() 
                : 0,
            'completed_evaluations' => Evaluation::where('nomination_status', NominationStatus::LOCKED)->count(),
            'pending_evaluations' => Evaluation::where('nomination_status', NominationStatus::PENDING)->count(),
        ];
    }

    public function getRecentActivity(int $limit = 10): array
    {
        return Log::with('user')
            ->orderBy('performed_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($log) {
                return [
                    'id' => $log->id,
                    'action_type' => $log->action_type,
                    'entity_type' => $log->entity_type,
                    'entity_id' => $log->entity_id,
                    'username' => $log->username,
                    'status' => $log->status,
                    'performed_at' => $log->performed_at,
                    'ip_address' => $log->ip_address,
                ];
            })
            ->toArray();
    }

    // Helper methods
    private function getStudentsByProgram(): array
    {
        return Student::join('programs', 'students.program_id', '=', 'programs.id')
            ->select('programs.program_name', DB::raw('count(*) as count'))
            ->groupBy('programs.program_name')
            ->pluck('count', 'program_name')
            ->toArray();
    }

    private function getStudentsByDepartment(): array
    {
        return Student::select('department', DB::raw('count(*) as count'))
            ->groupBy('department')
            ->pluck('count', 'department')
            ->toArray();
    }

    private function getStudentsByEvaluationType(): array
    {
        return Student::select('evaluation_type', DB::raw('count(*) as count'))
            ->groupBy('evaluation_type')
            ->pluck('count', 'evaluation_type')
            ->toArray();
    }

    private function getStudentsBySemester(): array
    {
        return Student::select('current_semester', DB::raw('count(*) as count'))
            ->groupBy('current_semester')
            ->orderBy('current_semester')
            ->pluck('count', 'current_semester')
            ->toArray();
    }

    private function getStudentsByProgramInDepartment(string $department): array
    {
        return Student::join('programs', 'students.program_id', '=', 'programs.id')
            ->where('students.department', $department)
            ->select('programs.program_name', DB::raw('count(*) as count'))
            ->groupBy('programs.program_name')
            ->pluck('count', 'program_name')
            ->toArray();
    }

    private function getStudentsByEvaluationStatusInDepartment(string $department): array
    {
        return Student::join('student_evaluations', 'students.id', '=', 'student_evaluations.student_id')
            ->where('students.department', $department)
            ->select('student_evaluations.nomination_status', DB::raw('count(*) as count'))
            ->groupBy('student_evaluations.nomination_status')
            ->pluck('count', 'nomination_status')
            ->toArray();
    }

    private function getStudentsAwaitingChairpersonAssignment(string $department): int
    {
        return Student::join('student_evaluations', 'students.id', '=', 'student_evaluations.student_id')
            ->where('students.department', $department)
            ->where('student_evaluations.nomination_status', NominationStatus::NOMINATED)
            ->whereNull('student_evaluations.chairperson_id')
            ->count();
    }

    private function getStudentsWithCompletedAssignments(string $department): int
    {
        return Student::join('student_evaluations', 'students.id', '=', 'student_evaluations.student_id')
            ->where('students.department', $department)
            ->where('student_evaluations.nomination_status', NominationStatus::LOCKED)
            ->count();
    }

    private function getStudentsWithPostponedEvaluations(string $department): int
    {
        return Student::join('student_evaluations', 'students.id', '=', 'student_evaluations.student_id')
            ->where('students.department', $department)
            ->where('student_evaluations.nomination_status', NominationStatus::POSTPONED)
            ->count();
    }

    private function getLockedNominationsCount(string $department): int
    {
        return Student::join('student_evaluations', 'students.id', '=', 'student_evaluations.student_id')
            ->where('students.department', $department)
            ->where('student_evaluations.nomination_status', NominationStatus::LOCKED)
            ->count();
    }

    private function getExaminersWorkload(string $department): array
    {
        return Evaluation::join('students', 'student_evaluations.student_id', '=', 'students.id')
            ->join('lecturers', function ($join) {
                $join->on('student_evaluations.examiner1_id', '=', 'lecturers.id')
                     ->orOn('student_evaluations.examiner2_id', '=', 'lecturers.id')
                     ->orOn('student_evaluations.examiner3_id', '=', 'lecturers.id');
            })
            ->where('students.department', $department)
            ->select('lecturers.name', 'lecturers.staff_number', DB::raw('count(*) as session_count'))
            ->groupBy('lecturers.id', 'lecturers.name', 'lecturers.staff_number')
            ->orderBy('session_count', 'desc')
            ->get()
            ->toArray();
    }

    private function getChairpersonsWorkload(string $department): array
    {
        return Evaluation::join('students', 'student_evaluations.student_id', '=', 'students.id')
            ->join('lecturers', 'student_evaluations.chairperson_id', '=', 'lecturers.id')
            ->where('students.department', $department)
            ->select('lecturers.name', 'lecturers.staff_number', DB::raw('count(*) as session_count'))
            ->groupBy('lecturers.id', 'lecturers.name', 'lecturers.staff_number')
            ->orderBy('session_count', 'desc')
            ->get()
            ->toArray();
    }

    private function getRecentNominations(int $lecturerId): array
    {
        return Evaluation::join('students', 'student_evaluations.student_id', '=', 'students.id')
            ->where('student_evaluations.nominated_by', $lecturerId)
            ->where('student_evaluations.nomination_status', '!=', NominationStatus::PENDING)
            ->select('students.name as student_name', 'students.matric_number', 'student_evaluations.nominated_at', 'student_evaluations.nomination_status')
            ->orderBy('student_evaluations.nominated_at', 'desc')
            ->limit(5)
            ->get()
            ->toArray();
    }

    private function getPendingNominationsList(int $lecturerId): array
    {
        return Student::where('main_supervisor_id', $lecturerId)
            ->whereDoesntHave('evaluations', function ($query) {
                $query->where('nomination_status', '!=', NominationStatus::PENDING);
            })
            ->with(['program'])
            ->select('name', 'matric_number', 'current_semester', 'program_id')
            ->get()
            ->toArray();
    }

    private function getLockedNominationsList(int $lecturerId): array
    {
        return Evaluation::join('students', 'student_evaluations.student_id', '=', 'students.id')
            ->where('student_evaluations.nominated_by', $lecturerId)
            ->where('student_evaluations.nomination_status', NominationStatus::LOCKED)
            ->select('students.name as student_name', 'students.matric_number', 'student_evaluations.locked_at')
            ->orderBy('student_evaluations.locked_at', 'desc')
            ->get()
            ->toArray();
    }

    private function getStudentsByNominationStatus(): array
    {
        return Evaluation::select('nomination_status', DB::raw('count(*) as count'))
            ->groupBy('nomination_status')
            ->pluck('count', 'nomination_status')
            ->toArray();
    }

    private function getStudentCountsByDepartment(): array
    {
        return Student::select('department', DB::raw('count(*) as count'))
            ->groupBy('department')
            ->pluck('count', 'department')
            ->toArray();
    }

    private function getEvaluationProgressByDepartment(): array
    {
        $departments = ['SEAT', 'II', 'BIHG', 'CAI'];
        $progress = [];

        foreach ($departments as $department) {
            $total = Student::where('department', $department)->count();
            $completed = Student::join('student_evaluations', 'students.id', '=', 'student_evaluations.student_id')
                ->where('students.department', $department)
                ->where('student_evaluations.nomination_status', NominationStatus::LOCKED)
                ->count();

            $progress[$department] = [
                'total' => $total,
                'completed' => $completed,
                'percentage' => $total > 0 ? round(($completed / $total) * 100, 2) : 0,
            ];
        }

        return $progress;
    }

    private function getWorkloadDistributionByDepartment(): array
    {
        $departments = ['SEAT', 'II', 'BIHG', 'CAI'];
        $distribution = [];

        foreach ($departments as $department) {
            $examinerSessions = Evaluation::join('students', 'student_evaluations.student_id', '=', 'students.id')
                ->where('students.department', $department)
                ->whereNotNull('student_evaluations.examiner1_id')
                ->count();

            $chairpersonSessions = Evaluation::join('students', 'student_evaluations.student_id', '=', 'students.id')
                ->where('students.department', $department)
                ->whereNotNull('student_evaluations.chairperson_id')
                ->count();

            $distribution[$department] = [
                'examiner_sessions' => $examinerSessions,
                'chairperson_sessions' => $chairpersonSessions,
            ];
        }

        return $distribution;
    }

    private function getTotalEvaluationsThisSemester(): int
    {
        $currentEvaluation = Evaluation::latest()->first();
        if (!$currentEvaluation) return 0;

        return Evaluation::where('semester', $currentEvaluation->semester)
            ->where('academic_year', $currentEvaluation->academic_year)
            ->count();
    }

    private function getAverageWorkloadPerExaminer(): float
    {
        $totalSessions = Evaluation::whereNotNull('examiner1_id')
            ->orWhereNotNull('examiner2_id')
            ->orWhereNotNull('examiner3_id')
            ->count();

        $uniqueExaminers = Evaluation::select('examiner1_id')
            ->whereNotNull('examiner1_id')
            ->union(
                Evaluation::select('examiner2_id')->whereNotNull('examiner2_id')
            )
            ->union(
                Evaluation::select('examiner3_id')->whereNotNull('examiner3_id')
            )
            ->distinct()
            ->count();

        return $uniqueExaminers > 0 ? round($totalSessions / $uniqueExaminers, 2) : 0;
    }

    private function getAverageWorkloadPerChairperson(): float
    {
        $totalSessions = Evaluation::whereNotNull('chairperson_id')->count();
        $uniqueChairpersons = Evaluation::whereNotNull('chairperson_id')
            ->distinct('chairperson_id')
            ->count('chairperson_id');

        return $uniqueChairpersons > 0 ? round($totalSessions / $uniqueChairpersons, 2) : 0;
    }

    private function getSystemUsageStatistics(): array
    {
        $last30Days = now()->subDays(30);
        
        return [
            'total_logins_last_30_days' => Log::where('action_type', 'LOGIN')
                ->where('performed_at', '>=', $last30Days)
                ->count(),
            'total_actions_last_30_days' => Log::where('performed_at', '>=', $last30Days)->count(),
            'successful_actions_last_30_days' => Log::where('status', 'SUCCESS')
                ->where('performed_at', '>=', $last30Days)
                ->count(),
            'failed_actions_last_30_days' => Log::where('status', 'FAILURE')
                ->where('performed_at', '>=', $last30Days)
                ->count(),
        ];
    }
}