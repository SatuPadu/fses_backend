<?php

namespace App\Services\Import;

use App\Enums\Department;
use App\Enums\EvaluationType;
use Illuminate\Support\Facades\Log;

class ImportValidator
{
    public function validateRow($row, $rowNumber)
    {
        // Main required fields (always required)
        $mainRequiredFields = [
            'student_matric_number', 'student_name', 'student_email', 'program_name',
            'current_semester', 'student_department', 'evaluation_type',
            'main_supervisor_staff_number', 'main_supervisor_name', 'main_supervisor_email',
            'main_supervisor_title', 'main_supervisor_department', 'main_supervisor_phone', 'main_supervisor_specialization'
        ];

        foreach ($mainRequiredFields as $field) {
            if (empty($row[$field])) {
                Log::error("Required field missing", ['field' => $field, 'row' => $rowNumber]);
                throw new \Exception("Required field '{$field}' is missing or empty");
            }
        }

        // Boolean validation for main_supervisor_is_coordinator
        if (!isset($row['main_supervisor_is_coordinator']) ||
            !in_array(strtolower($row['main_supervisor_is_coordinator']), ['yes', 'no', 'true', 'false', '1', '0', ''])) {
            Log::error("Invalid boolean for main_supervisor_is_coordinator", ['value' => $row['main_supervisor_is_coordinator'], 'row' => $rowNumber]);
            throw new \Exception("main_supervisor_is_coordinator must be a boolean value (yes/no, true/false, 1/0)");
        }

        // Check if all co-supervisor fields are present (all or none logic)
        $coSupervisorFields = [
            'co_supervisor_staff_number', 'co_supervisor_name', 'co_supervisor_title',
            'co_supervisor_department', 'co_supervisor_is_coordinator', 'co_supervisor_email',
            'co_supervisor_phone', 'co_supervisor_specialization', 'co_supervisor_external_institution'
        ];
        $allCoSupervisorEmpty = true;
        $allCoSupervisorFilled = true;
        foreach ($coSupervisorFields as $field) {
            if (!empty($row[$field])) {
                $allCoSupervisorEmpty = false;
            } else {
                $allCoSupervisorFilled = false;
            }
        }
        if (!$allCoSupervisorEmpty && !$allCoSupervisorFilled) {
            Log::error("Co-supervisor information is incomplete (all-or-none rule)", ['row' => $rowNumber]);
            throw new \Exception("Co-supervisor information must be either fully filled or fully empty (all-or-none rule)");
        }
        if ($allCoSupervisorFilled) {
            // Boolean validation for co_supervisor_is_coordinator
            if (!isset($row['co_supervisor_is_coordinator']) ||
                !in_array(strtolower($row['co_supervisor_is_coordinator']), ['yes', 'no', 'true', 'false', '1', '0', ''])) {
                Log::error("Invalid boolean for co_supervisor_is_coordinator", ['value' => $row['co_supervisor_is_coordinator'], 'row' => $rowNumber]);
                throw new \Exception("co_supervisor_is_coordinator must be a boolean value (yes/no, true/false, 1/0)");
            }
        }

        // Validate enum values
        if (!Department::isValid($row['student_department'])) {
            Log::error("Invalid department", [
                'department' => $row['student_department'],
                'validDepartments' => Department::all(),
                'row' => $rowNumber
            ]);
            throw new \Exception("Invalid department: {$row['student_department']}");
        }

        if (!EvaluationType::isValid($row['evaluation_type'])) {
            Log::error("Invalid evaluation type", [
                'evaluationType' => $row['evaluation_type'],
                'validTypes' => EvaluationType::all(),
                'row' => $rowNumber
            ]);
            throw new \Exception("Invalid evaluation type: {$row['evaluation_type']}");
        }

        $programMapping = [
            'Doctor of Philosophy' => [
                'code' => 'PhD',
                'total_semesters' => 16,
                'evaluation_semester' => 3,
            ],
            'Master of Philosophy' => [
                'code' => 'MPhil',
                'total_semesters' => 8,
                'evaluation_semester' => 2,
            ],
            'Doctor of Software Engineering' => [
                'code' => 'DSE',
                'total_semesters' => 16,
                'evaluation_semester' => [3, 5],
            ]
        ];
        $programName = $row['program_name'];
        $currentSemester = (int)($row['current_semester'] ?? 0);
        $isReEvaluation = ($row['evaluation_type'] ?? null) === 'ReEvaluation';
        if (isset($programMapping[$programName])) {
            $evalSem = $programMapping[$programName]['evaluation_semester'];
            $totalSem = $programMapping[$programName]['total_semesters'];
            if ($isReEvaluation) {
                if ($programName === 'Doctor of Software Engineering') {
                    if ($currentSemester <= 5 || $currentSemester > $totalSem) {
                        throw new \Exception("For Re-Evaluation, {$programName} students must be in semester greater than 5 and up to {$totalSem}, found semester {$currentSemester}");
                    }
                } else {
                    if ($currentSemester <= $evalSem || $currentSemester > $totalSem) {
                        throw new \Exception("For Re-Evaluation, {$programName} students must be in semester greater than {$evalSem} and up to {$totalSem}, found semester {$currentSemester}");
                    }
                }
            } else {
                if ($programName === 'Doctor of Software Engineering') {
                    if (!in_array($currentSemester, [3, 5])) {
                        throw new \Exception("The allowed semesters for first evaluation for {$programName} students are: 3, 5; found semester {$currentSemester}");
                    }
                } else {
                    if ($currentSemester != $evalSem) {
                        throw new \Exception("The allowed semester for first evaluation for {$programName} students is {$evalSem}, found semester {$currentSemester}");
                    }
                }
            }
        }
    }

    public function hasCompleteCoSupervisorData(array $row): bool
    {
        $fields = [
            'co_supervisor_staff_number', 'co_supervisor_name', 'co_supervisor_title',
            'co_supervisor_department', 'co_supervisor_is_coordinator', 'co_supervisor_email',
            'co_supervisor_phone', 'co_supervisor_specialization', 'co_supervisor_external_institution'
        ];
        foreach ($fields as $field) {
            if (empty($row[$field])) return false;
        }
        return true;
    }

    // hasPartialCoSupervisorData removed: all-or-none logic enforced in validateRow
}
