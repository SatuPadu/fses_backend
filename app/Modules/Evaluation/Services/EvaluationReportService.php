<?php

namespace App\Modules\Evaluation\Services;

use App\Modules\Evaluation\Models\Evaluation;
use App\Modules\UserManagement\Models\Lecturer;
use App\Modules\Program\Models\Program;
use App\Enums\EvaluationType;
use App\Enums\ProgramName;
use App\Enums\NominationStatus;

class EvaluationReportService
{
    /**
     * Get first stage evaluation and postponed counts for each program.
     *
     * @return array
     */
    public function getFirstStageEvaluationSummary(): array
    {
        $programs = Program::whereIn('program_name', ProgramName::all())->get();
        $firstStageSem = [
            ProgramName::PHD => 3,
            ProgramName::MPHIL => 2,
            ProgramName::DSE => [3, 5],
        ];
        $result = [];
        foreach ($programs as $program) {
            $students = $program->students;
            $firstStageStudents = $students->filter(function($student) use ($firstStageSem, $program) {
                if ($program->program_name === ProgramName::DSE) {
                    return in_array($student->current_semester, $firstStageSem[ProgramName::DSE]);
                }
                return $student->current_semester == $firstStageSem[$program->program_name];
            });
            $firstStageIds = $firstStageStudents->pluck('id');
            $total = $firstStageStudents->count();
            if ($program->program_name === ProgramName::DSE) {
                $postponed = Evaluation::whereIn('student_id', $firstStageIds)
                    ->whereIn('semester', $firstStageSem[ProgramName::DSE])
                    ->where('nomination_status', NominationStatus::POSTPONED)
                    ->count();
            } else {
                $postponed = Evaluation::whereIn('student_id', $firstStageIds)
                    ->where('semester', $firstStageSem[$program->program_name])
                    ->where('nomination_status', NominationStatus::POSTPONED)
                    ->count();
            }
            $result[] = [
                'program' => $program->program_name,
                'first_stage' => $total,
                'postponed' => $postponed
            ];
        }
        return $result;
    }

    /**
     * Get evaluation data for chart visualization grouped by program and evaluation type.
     * Returns data matching the chart structure with categories:
     * - Sem 2 (PhD)
     * - Sem 3 (PhD), Sem 2 (MPhil) 
     * - Sem 4 (PhD) & Sem 3 (MPhil) and above
     * - Re-PD
     *
     * @param string|null $academicYear Filter by academic year
     * @return array
     */
    public function getEvaluationChartData($academicYear = null): array
    {
        $programs = Program::whereIn('program_name', [
            ProgramName::PHD, 
            ProgramName::MPHIL, 
            ProgramName::DSE
        ])->with(['students.evaluations'])->get();

        $result = [];

        foreach ($programs as $program) {
            $programData = [
                'program' => $program->program_name,
                'sem_2_phd' => 0,
                'sem_3_phd_sem_2_mphil' => 0,
                'sem_4_phd_sem_3_mphil_above' => 0,
                're_pd' => 0
            ];

            foreach ($program->students as $student) {
                // Filter evaluations by academic year if provided
                $evaluations = $student->evaluations;
                if ($academicYear) {
                    $evaluations = $evaluations->where('academic_year', $academicYear);
                }

                foreach ($evaluations as $evaluation) {
                    // Handle Re-PD category (assuming Re-PD is an evaluation_type)
                    if ($student->evaluation_type === 'Re-PD') {
                        $programData['re_pd']++;
                        continue;
                    }

                    // Category logic based on program and semester
                    switch ($program->program_name) {
                        case ProgramName::PHD:
                            if ($evaluation->semester == 2) {
                                $programData['sem_2_phd']++;
                            } elseif ($evaluation->semester == 3) {
                                $programData['sem_3_phd_sem_2_mphil']++;
                            } elseif ($evaluation->semester >= 4) {
                                $programData['sem_4_phd_sem_3_mphil_above']++;
                            }
                            break;

                        case ProgramName::MPHIL:
                            if ($evaluation->semester == 2) {
                                $programData['sem_3_phd_sem_2_mphil']++;
                            } elseif ($evaluation->semester >= 3) {
                                $programData['sem_4_phd_sem_3_mphil_above']++;
                            }
                            break;

                        case ProgramName::DSE:
                            // DSE logic - adjust based on your specific requirements
                            if (in_array($evaluation->semester, [3, 5])) {
                                $programData['sem_3_phd_sem_2_mphil']++;
                            }
                            break;
                    }
                }
            }

            $result[] = $programData;
        }

        return $result;
    }

    /**
     * Alternative method that gets chart data based on current student semesters
     * instead of evaluation records (if that's what your chart represents)
     *
     * @param string|null $academicYear Filter by academic year
     * @return array
     */
    public function getCurrentSemesterChartData($academicYear = null): array
    {
        // Get all unique program codes
        $programCodes = Program::query()->distinct()->pluck('program_code')->toArray();

        $result = [];

        foreach ($programCodes as $code) {
            // Get all programs with this code (across departments)
            $programs = Program::where('program_code', $code)->get();

            // Aggregate all students for this program code
            $students = collect();
            foreach ($programs as $program) {
                $students = $students->merge($program->students);
            }

            // Only consider students with at least one locked evaluation
            $students = $students->filter(function ($student) use ($academicYear) {
                $lockedEvals = $student->evaluations()
                    ->where('nomination_status', 'Locked');
                if ($academicYear) {
                    $lockedEvals = $lockedEvals->where('academic_year', $academicYear);
                }
                return $lockedEvals->count() > 0;
            });

            $programData = [
                'program' => $code,
                'sem_2_phd' => 0,
                'sem_3_phd_sem_2_mphil' => 0,
                'sem_4_phd_sem_3_mphil_above' => 0,
                're_pd' => 0
            ];

            foreach ($students as $student) {
                // Get the student's locked evaluation(s) for this academic year
                $lockedEvals = $student->evaluations()
                    ->where('nomination_status', 'Locked');
                if ($academicYear) {
                    $lockedEvals = $lockedEvals->where('academic_year', $academicYear);
                }
                $lockedEvals = $lockedEvals->get();

                foreach ($lockedEvals as $eval) {
                    // Handle Re-Evaluation category
                    if ($student->evaluation_type === EvaluationType::RE_EVALUATION) {
                        $programData['re_pd']++;
                        continue;
                    }

                    // Category logic based on program code and evaluation semester
                    switch ($code) {
                        case 'PhD':
                            if ($eval->semester == 2) {
                                $programData['sem_2_phd']++;
                            } elseif ($eval->semester == 3) {
                                $programData['sem_3_phd_sem_2_mphil']++;
                            } elseif ($eval->semester >= 4) {
                                $programData['sem_4_phd_sem_3_mphil_above']++;
                            }
                            break;

                        case 'MPhil':
                            if ($eval->semester == 2) {
                                $programData['sem_3_phd_sem_2_mphil']++;
                            } elseif ($eval->semester >= 3) {
                                $programData['sem_4_phd_sem_3_mphil_above']++;
                            }
                            break;

                        case 'DSE':
                            if (in_array($eval->semester, [3, 5])) {
                                $programData['sem_3_phd_sem_2_mphil']++;
                            }
                            break;
                    }
                }
            }

            $result[$code] = $programData;
        }

        // Return as a numerically indexed array for charting
        return array_values($result);
    }

    /**
     * Get formatted chart data ready for frontend consumption
     *
     * @param string|null $academicYear
     * @return array
     */
    public function getFormattedChartData($academicYear = null): array
    {
        $data = $this->getCurrentSemesterChartData($academicYear);
        
        $categories = [
            'sem_2_phd' => 'Sem 2 (PhD)',
            'sem_3_phd_sem_2_mphil' => 'Sem 3 (PhD) Sem 2 (MPhil)',
            'sem_4_phd_sem_3_mphil_above' => 'Sem 4 (PhD) & Sem 3 (MPhil) and above',
            're_pd' => 'Re-PD'
        ];

        $chartData = [
            'categories' => array_values($categories),
            'series' => []
        ];

        foreach ($categories as $key => $label) {
            $seriesData = [];
            foreach ($data as $programData) {
                $seriesData[] = $programData[$key];
            }
            
            $chartData['series'][] = [
                'name' => $label,
                'data' => $seriesData
            ];
        }

        $chartData['programs'] = array_column($data, 'program');

        return $chartData;
    }

    /**
     * Get unique examiners for dropdown.
     */
    public function getUniqueExaminers()
    {
        // Get unique examiner IDs from all evaluations
        $examinerIds = Evaluation::query()
            ->selectRaw('examiner1_id as id')->whereNotNull('examiner1_id')
            ->union(
                Evaluation::query()->selectRaw('examiner2_id as id')->whereNotNull('examiner2_id')
            )
            ->union(
                Evaluation::query()->selectRaw('examiner3_id as id')->whereNotNull('examiner3_id')
            )
            ->pluck('id')
            ->unique()
            ->filter();

        return Lecturer::whereIn('id', $examinerIds)
            ->select('id', 'name', 'title', 'staff_number')
            ->get();
    }

    /**
     * Get examiner session count by lecturer and academic year.
     * If academic year is not provided, returns total count and breakdown for last 3 years.
     * If academic year is provided, returns count for that specific year only.
     */
    public function getExaminerSessions($lecturerId, $academicYear = null)
    {
        $baseQuery = Evaluation::query()->where(function($q) use ($lecturerId) {
            $q->where('examiner1_id', $lecturerId)
              ->orWhere('examiner2_id', $lecturerId)
              ->orWhere('examiner3_id', $lecturerId);
        });

        if ($academicYear) {
            // If specific academic year is provided, return only that year's data
            $count = $baseQuery->where('academic_year', $academicYear)->count();
            return [
                'total' => $count,
                'breakdown' => [
                    $academicYear => $count
                ]
            ];
        } else {
            // If no academic year provided, get total and breakdown for last 3 years
            $totalCount = $baseQuery->count();
            
            // Get breakdown for last 3 academic years
            $breakdown = $baseQuery->selectRaw('academic_year, COUNT(*) as count')
                ->groupBy('academic_year')
                ->orderBy('academic_year', 'desc')
                ->limit(3)
                ->pluck('count', 'academic_year')
                ->toArray();

            return [
                'total' => $totalCount,
                'breakdown' => $breakdown
            ];
        }
    }

    /**
     * Get unique chairpersons for dropdown.
     */
    public function getUniqueChairpersons()
    {
        return Lecturer::whereHas('chairpersonEvaluations')
            ->select('id', 'name', 'title', 'staff_number')
            ->distinct()
            ->get();
    }

    /**
     * Get chairperson session count by lecturer and academic year.
     */
    public function getChairpersonSessions($lecturerId, $academicYear)
    {
        $query = Evaluation::query()->where('chairperson_id', $lecturerId);
        if ($academicYear) {
            $query->where('academic_year', $academicYear);
        }
        return $query->count();
    }

    /**
     * Get locked evaluation counts grouped by program_code, semester, and evaluation_type.
     * Filterable by program_code and academic_year. Returns chart-ready data.
     */
    public function getEvaluationSummaryByProgramSemesterType($programCode = null, $academicYear = null)
    {
        $query = Evaluation::query()
            ->join('students', 'student_evaluations.student_id', '=', 'students.id')
            ->join('programs', 'students.program_id', '=', 'programs.id')
            ->where('student_evaluations.nomination_status', 'Locked');

        if ($programCode) {
            $query->where('programs.program_code', $programCode);
        }
        if ($academicYear) {
            $query->where('student_evaluations.academic_year', $academicYear);
        }

        $evaluations = $query->get([
            'programs.program_code',
            'student_evaluations.semester',
            'students.evaluation_type'
        ]);

        // Group and count
        $result = [];
        foreach ($evaluations as $eval) {
            $code = $eval->program_code;
            $sem = $eval->semester;
            $type = $eval->evaluation_type;

            if (!isset($result[$code])) $result[$code] = [];
            if (!isset($result[$code][$sem])) $result[$code][$sem] = ['FirstEvaluation' => 0, 'ReEvaluation' => 0];

            if ($type === 'FirstEvaluation') {
                $result[$code][$sem]['FirstEvaluation']++;
            } elseif ($type === 'ReEvaluation') {
                $result[$code][$sem]['ReEvaluation']++;
            }
        }

        // Collect all unique semesters
        $allSemesters = [];
        foreach ($result as $code => $semesters) {
            foreach ($semesters as $sem => $counts) {
                $allSemesters[$sem] = true;
            }
        }
        $allSemesters = array_keys($allSemesters);
        sort($allSemesters);

        // Format for charting
        $chartData = [
            'programs' => array_keys($result),
            'semesters' => $allSemesters,
            'series' => [
                ['name' => 'FirstEvaluation', 'data' => []],
                ['name' => 'ReEvaluation', 'data' => []]
            ]
        ];

        // Fill series data
        foreach ($chartData['programs'] as $code) {
            foreach ($chartData['semesters'] as $sem) {
                $first = $result[$code][$sem]['FirstEvaluation'] ?? 0;
                $re = $result[$code][$sem]['ReEvaluation'] ?? 0;
                $chartData['series'][0]['data'][] = $first;
                $chartData['series'][1]['data'][] = $re;
            }
        }

        return $chartData;
    }
}