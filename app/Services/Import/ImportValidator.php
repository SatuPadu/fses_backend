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
