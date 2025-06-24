<?php

namespace App\Services\Import;

use App\Enums\Department;
use App\Enums\LecturerTitle;
use App\Enums\EvaluationType;
use App\Enums\NominationStatus;
use Illuminate\Support\Facades\Log;

class ImportValidator
{
    public function validateRow($row, $rowNumber)
    {
        $requiredFields = [
            'student_matric_number', 'student_name', 'student_email', 'program_name',
            'current_semester', 'student_department', 'evaluation_type',
            'main_supervisor_staff_number', 'main_supervisor_name', 'main_supervisor_email',
            'examiner1_staff_number', 'examiner1_name', 'examiner1_email',
            'examiner2_staff_number', 'examiner2_name', 'examiner2_email',
            'examiner3_staff_number', 'examiner3_name', 'examiner3_email',
            'chairperson_staff_number', 'chairperson_name', 'chairperson_email'
        ];

        foreach ($requiredFields as $field) {
            if (empty($row[$field])) {
                Log::error("Required field missing", ['field' => $field, 'row' => $rowNumber]);
                throw new \Exception("Required field '{$field}' is missing or empty");
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

        if (!NominationStatus::isValid($row['nomination_status'])) {
            Log::error("Invalid nomination status", [
                'nominationStatus' => $row['nomination_status'],
                'validStatuses' => NominationStatus::all(),
                'row' => $rowNumber
            ]);
            throw new \Exception("Invalid nomination status: {$row['nomination_status']}");
        }
    }
} 