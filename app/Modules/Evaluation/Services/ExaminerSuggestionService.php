<?php

namespace App\Modules\Evaluation\Services;

use App\Modules\UserManagement\Models\Lecturer;
use App\Modules\Student\Models\Student;
use App\Enums\LecturerTitle;
use Illuminate\Support\Facades\DB;

class ExaminerSuggestionService
{
    /**
     * Get suggestions for Examiner 1
     *
     * @param int $studentId
     * @return \Illuminate\Database\Eloquent\Collection
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function getExaminer1Suggestions(int $studentId)
    {
        $student = Student::with(['mainSupervisor'])->findOrFail($studentId);
        
        // Get main supervisor's title
        $mainSupervisorTitle = $student->mainSupervisor->title;
        
        // Build query for Examiner 1 suggestions
        $query = Lecturer::where('is_from_fai', true) // Must be from faculty
            ->where('id', '!=', $student->main_supervisor_id) // Cannot be the main supervisor
            ->whereNotIn('id', function($subQuery) use ($studentId) {
                // Cannot be co-supervisor
                $subQuery->select('lecturer_id')
                    ->from('co_supervisors')
                    ->where('student_id', $studentId);
            });
        
        // Apply title requirements based on main supervisor
        if ($mainSupervisorTitle === LecturerTitle::PROFESSOR) {
            // If main supervisor is Professor, Examiner 1 must be Professor
            $query->where('title', LecturerTitle::PROFESSOR);
        } else {
            // Otherwise, Examiner 1 must be at least Associate Professor
            $query->whereIn('title', [LecturerTitle::PROFESSOR, LecturerTitle::PROFESSOR_MADYA]);
        }
        
        // Exclude lecturers who are already examiners for this student
        $query->whereNotIn('id', function($subQuery) use ($studentId) {
            $subQuery->select('examiner1_id')
                ->from('student_evaluations')
                ->where('student_id', $studentId)
                ->whereNotNull('examiner1_id')
                ->union(
                    DB::table('student_evaluations')
                        ->select('examiner2_id')
                        ->where('student_id', $studentId)
                        ->whereNotNull('examiner2_id')
                )
                ->union(
                    DB::table('student_evaluations')
                        ->select('examiner3_id')
                        ->where('student_id', $studentId)
                        ->whereNotNull('examiner3_id')
                );
        });
        
        return $query->orderBy('name')->get();
    }

    /**
     * Get suggestions for Examiner 2
     *
     * @param int $studentId
     * @param int|null $examiner1Id Currently selected Examiner 1 ID
     * @return \Illuminate\Database\Eloquent\Collection
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function getExaminer2Suggestions(int $studentId, ?int $examiner1Id = null)
    {
        $student = Student::with(['mainSupervisor'])->findOrFail($studentId);
        
        // Build query for Examiner 2 suggestions
        $query = Lecturer::where('id', '!=', $student->main_supervisor_id) // Cannot be the main supervisor
            ->whereNotIn('id', function($subQuery) use ($studentId) {
                // Cannot be co-supervisor
                $subQuery->select('lecturer_id')
                    ->from('co_supervisors')
                    ->where('student_id', $studentId);
            });
        
        // Examiner 2 can be from any faculty/university (no is_from_fai restriction)
        // Advised to be Associate Professor but not compulsory
        $query->whereIn('title', [
            LecturerTitle::PROFESSOR, 
            LecturerTitle::PROFESSOR_MADYA, 
            LecturerTitle::DR
        ]);
        
        // Exclude currently selected examiners (Examiner 1 should not appear in Examiner 2's list)
        $excludeIds = [];
        if ($examiner1Id) $excludeIds[] = $examiner1Id;
        
        if (!empty($excludeIds)) {
            $query->whereNotIn('id', $excludeIds);
        }
        
        // Exclude lecturers who are already examiners for this student
        $query->whereNotIn('id', function($subQuery) use ($studentId) {
            $subQuery->select('examiner1_id')
                ->from('student_evaluations')
                ->where('student_id', $studentId)
                ->whereNotNull('examiner1_id')
                ->union(
                    DB::table('student_evaluations')
                        ->select('examiner2_id')
                        ->where('student_id', $studentId)
                        ->whereNotNull('examiner2_id')
                )
                ->union(
                    DB::table('student_evaluations')
                        ->select('examiner3_id')
                        ->where('student_id', $studentId)
                        ->whereNotNull('examiner3_id')
                );
        });
        
        return $query->orderBy('name')->get();
    }

    /**
     * Get suggestions for Examiner 3
     *
     * @param int $studentId
     * @param int|null $examiner1Id Currently selected Examiner 1 ID
     * @param int|null $examiner2Id Currently selected Examiner 2 ID
     * @return \Illuminate\Database\Eloquent\Collection
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function getExaminer3Suggestions(int $studentId, ?int $examiner1Id = null, ?int $examiner2Id = null)
    {
        $student = Student::with(['mainSupervisor'])->findOrFail($studentId);
        
        // Build query for Examiner 3 suggestions
        $query = Lecturer::where('is_from_fai', true) // Must be from faculty
            ->where('id', '!=', $student->main_supervisor_id) // Cannot be the main supervisor
            ->whereNotIn('id', function($subQuery) use ($studentId) {
                // Cannot be co-supervisor
                $subQuery->select('lecturer_id')
                    ->from('co_supervisors')
                    ->where('student_id', $studentId);
            });
        
        // Examiner 3 can be Dr. (any title is acceptable)
        $query->whereIn('title', [
            LecturerTitle::PROFESSOR, 
            LecturerTitle::PROFESSOR_MADYA, 
            LecturerTitle::DR
        ]);
        
        // Exclude currently selected examiners (Examiners 1 and 2 should not appear in Examiner 3's list)
        $excludeIds = [];
        if ($examiner1Id) $excludeIds[] = $examiner1Id;
        if ($examiner2Id) $excludeIds[] = $examiner2Id;
        
        if (!empty($excludeIds)) {
            $query->whereNotIn('id', $excludeIds);
        }
        
        // Exclude lecturers who are already examiners for this student
        $query->whereNotIn('id', function($subQuery) use ($studentId) {
            $subQuery->select('examiner1_id')
                ->from('student_evaluations')
                ->where('student_id', $studentId)
                ->whereNotNull('examiner1_id')
                ->union(
                    DB::table('student_evaluations')
                        ->select('examiner2_id')
                        ->where('student_id', $studentId)
                        ->whereNotNull('examiner2_id')
                )
                ->union(
                    DB::table('student_evaluations')
                        ->select('examiner3_id')
                        ->where('student_id', $studentId)
                        ->whereNotNull('examiner3_id')
                );
        });
        
        return $query->orderBy('name')->get();
    }
} 