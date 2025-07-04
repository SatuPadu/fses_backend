<?php

namespace App\Modules\Student\Services;

use App\Enums\Department;
use App\Enums\LecturerTitle;
use App\Enums\EvaluationType;
use App\Enums\NominationStatus;
use App\Enums\ProgramName;

class StudentEvaluationImportService
{
    /**
     * Validate a single row of import data
     */
    public function validateRow(array $row, int $rowNumber): array
    {
        $errors = [];

        // STEP 1: Always validate basic required fields
        $errors = array_merge($errors, $this->validateBasicRequiredFields($row));

        // STEP 2: Validate common fields (emails, semester, enums, program)
        $errors = array_merge($errors, $this->validateCommonFields($row));

        return $errors;
    }

    /**
     * Validate basic required fields that every row must have.
     * Required fields include everything up to main_supervisor_specialization.
     * Co-supervisor fields have different requirements based on external_institution.
     */
    protected function validateBasicRequiredFields(array $row): array
    {
        $errors = [];
        
        // Main required fields (always required)
        $mainRequiredFields = [
            'student_matric_number', 'student_name', 'student_email', 'program_name',
            'current_semester', 'student_department', 'evaluation_type', 'country', 'research_title',
            'main_supervisor_staff_number', 'main_supervisor_name', 'main_supervisor_title', 'main_supervisor_department',
            'main_supervisor_email', 'main_supervisor_phone', 'main_supervisor_specialization'
        ];

        // First validate all main required fields
        foreach ($mainRequiredFields as $field) {
            if (empty($row[$field])) {
                $errors[] = "Required field '{$field}' is missing or empty";
            }
        }

        // Special handling for boolean fields
        if (!isset($row['main_supervisor_is_coordinator']) || 
            !in_array(strtolower($row['main_supervisor_is_coordinator']), ['yes', 'no', 'true', 'false', '1', '0', ''])) {
            $errors[] = "main_supervisor_is_coordinator must be a boolean value (yes/no, true/false, 1/0)";
        }

        // Check if any co-supervisor fields are present
        $hasCoSupervisorData = $this->hasPartialCoSupervisorData($row);
        
        if ($hasCoSupervisorData) {
            // Check if external institution is provided
            $hasExternalInstitution = !empty($row['co_supervisor_external_institution']);
            
            if ($hasExternalInstitution) {
                // External co-supervisor: only core fields required
                $coSupervisorRequiredFields = [
                    'co_supervisor_name', 'co_supervisor_title', 'co_supervisor_department',
                    'co_supervisor_email', 'co_supervisor_specialization'
                ];
                
                foreach ($coSupervisorRequiredFields as $field) {
                    if (empty($row[$field])) {
                        $errors[] = "External co-supervisor information is incomplete. Field '{$field}' is required for external co-supervisors";
                    }
                }
            } else {
                // Internal co-supervisor: all fields required except external_institution
                $coSupervisorRequiredFields = [
                    'co_supervisor_staff_number', 'co_supervisor_name', 'co_supervisor_title', 'co_supervisor_department',
                    'co_supervisor_email', 'co_supervisor_phone', 'co_supervisor_specialization'
                ];

                foreach ($coSupervisorRequiredFields as $field) {
                    if (empty($row[$field])) {
                        $errors[] = "Internal co-supervisor information is incomplete. Field '{$field}' is required for internal co-supervisors";
                    }
                }

                // Validate co-supervisor boolean field for internal co-supervisors
                if (!isset($row['co_supervisor_is_coordinator']) || 
                    !in_array(strtolower($row['co_supervisor_is_coordinator']), ['yes', 'no', 'true', 'false', '1', '0', ''])) {
                    $errors[] = "co_supervisor_is_coordinator must be a boolean value (yes/no, true/false, 1/0) for internal co-supervisors";
                }
            }
        }

        return $errors;
    }

    /**
     * Check if any co-supervisor fields contain data
     */
    protected function hasPartialCoSupervisorData(array $row): bool
    {
        $coSupervisorFields = [
            'co_supervisor_staff_number', 'co_supervisor_name', 'co_supervisor_title',
            'co_supervisor_department', 'co_supervisor_email', 'co_supervisor_phone',
            'co_supervisor_specialization', 'co_supervisor_external_institution'
        ];

        foreach ($coSupervisorFields as $field) {
            if (!empty($row[$field])) return true;
        }
        return false;
    }

    /**
     * Validate common fields that apply to all rows when present
     */
    protected function validateCommonFields(array $row): array
    {
        $errors = [];

        // Email validation (required emails must be validated)
        $requiredEmails = ['student_email', 'main_supervisor_email', 'co_supervisor_email'];
        foreach ($requiredEmails as $field) {
            if (!empty($row[$field]) && !filter_var($row[$field], FILTER_VALIDATE_EMAIL)) {
                $errors[] = "Invalid email format for '{$field}': {$row[$field]}";
            }
        }

        // Semester validation
        if (!empty($row['current_semester'])) {
            $semester = (int)$row['current_semester'];
            if ($semester < 1 || $semester > 20) {
                $errors[] = "Invalid semester number: {$row['current_semester']}. Must be between 1-20";
            }
        }

        // Enum validation (only for present fields)
        if (!empty($row['student_department']) && !Department::isValid($row['student_department'])) {
            $errors[] = "Invalid department: {$row['student_department']}";
        }
        if (!empty($row['evaluation_type']) && !EvaluationType::isValid($row['evaluation_type'])) {
            $errors[] = "Invalid evaluation type: {$row['evaluation_type']}";
        }

        // Lecturer title validation (required and optional)
        $lecturerTitles = [
            'main_supervisor_title', 'co_supervisor_title'
        ];
        foreach ($lecturerTitles as $titleField) {
            if (!empty($row[$titleField]) && !LecturerTitle::isValid($row[$titleField])) {
                $errors[] = "Invalid lecturer title '{$row[$titleField]}' for {$titleField}";
            }
        }

        // Program validation with mapping
        if (!empty($row['program_name'])) {
            $trimmedProgramName = trim($row['program_name']);
            
            // Map full program names to enum values
            $programNameMapping = [
                'Doctor of Philosophy' => ProgramName::PHD,
                'Master of Philosophy' => ProgramName::MPHIL,
                'Doctor of Software Engineering' => ProgramName::DSE
            ];
            
            $enumProgramName = $programNameMapping[$trimmedProgramName] ?? $trimmedProgramName;
            
            if (!ProgramName::isValid($enumProgramName)) {
                $errors[] = "Invalid program name: {$row['program_name']}. Valid options are: Doctor of Philosophy, Master of Philosophy, Doctor of Software Engineering, or the short forms: " . implode(', ', ProgramName::all());
            } else {
                // Validate semester for program type using the mapped enum value
                $semester = (int)($row['current_semester'] ?? 0);
                $programMapping = [
                    ProgramName::PHD => ['evaluation_semester' => [3], 'total_semesters' => 16],
                    ProgramName::MPHIL => ['evaluation_semester' => [2], 'total_semesters' => 8],
                    ProgramName::DSE => ['evaluation_semester' => [3, 5], 'total_semesters' => 16],
                ];
                if (isset($programMapping[$enumProgramName])) {
                    $evalSemArr = $programMapping[$enumProgramName]['evaluation_semester'];
                    $totalSem = $programMapping[$enumProgramName]['total_semesters'];
                    $isReEvaluation = ($row['evaluation_type'] ?? null) === 'ReEvaluation';
                    if ($semester > 0) {
                        if ($isReEvaluation) {
                            $maxEvalSem = is_array($evalSemArr) ? max($evalSemArr) : $evalSemArr;
                            if ($semester <= $maxEvalSem || $semester > $totalSem) {
                                $errors[] = "For Re-Evaluation, {$trimmedProgramName} students must be in semester greater than {$maxEvalSem} and up to {$totalSem}, found semester {$semester}";
                            }
                        } else {
                            if (is_array($evalSemArr)) {
                                if (!in_array($semester, $evalSemArr)) {
                                    $errors[] = "The allowed semesters for first evaluation for {$trimmedProgramName} students are: " . implode(', ', $evalSemArr) . ", found semester {$semester}";
                                }
                            } else {
                                if ($semester != $evalSemArr) {
                                    $errors[] = "The allowed semester for first evaluation for {$trimmedProgramName} students is {$evalSemArr}, found semester {$semester}";
                                }
                            }
                        }
                    }
                }
            }
        }

        return $errors;
    }

    /**
     * Get program mapping for different program types
     * Updated to use the new enum values and mapping
     */
    public function getProgramMapping(): array
    {
        return [
            'Doctor of Philosophy' => [
                'code' => 'PhD',
                'total_semesters' => 16,
                'evaluation_semester' => [3]
            ],
            'Master of Philosophy' => [
                'code' => 'MPhil',
                'total_semesters' => 8,
                'evaluation_semester' => [2]
            ],
            'Doctor of Software Engineering' => [
                'code' => 'DSE',
                'total_semesters' => 16,
                'evaluation_semester' => [3, 5]
            ]
        ];
    }

    /**
     * Check if lecturer is external (only co-supervisor can be external)
     */
    public function isExternalLecturer(string $type, array $row): bool
    {
        switch ($type) {
            case 'co_supervisor':
                return !empty($row['co_supervisor_external_institution']);
            default:
                return false;
        }
    }

    /**
     * Generate summary statistics for import
     */
    public function generateImportSummary(array $results): array
    {
        $summary = [
            'total_rows' => count($results),
            'successful' => 0,
            'failed' => 0,
            'skipped' => 0,
            'programs_created' => 0,
            'programs_updated' => 0,
            'lecturers_created' => 0,
            'lecturers_updated' => 0,
            'students_created' => 0,
            'students_updated' => 0,
            'users_created' => 0,
            'users_updated' => 0,
            'errors' => []
        ];

        foreach ($results as $result) {
            if (isset($result['skipped']) && $result['skipped']) {
                $summary['skipped']++;
            } elseif ($result['success']) {
                $summary['successful']++;

                // Update creation/update counts
                foreach ($result as $key => $value) {
                    if (isset($summary[$key]) && is_numeric($value)) {
                        $summary[$key] += $value;
                    }
                }
            } else {
                $summary['failed']++;
                if (isset($result['error'])) {
                    $summary['errors'][] = $result['error'];
                }
            }
        }

        return $summary;
    }

    /**
     * Check if all co-supervisor fields are present and non-empty
     * This method is updated to handle external vs internal co-supervisors
     */
    public function hasCompleteCoSupervisorData(array $row): bool
    {
        $hasExternalInstitution = !empty($row['co_supervisor_external_institution']);
        
        if ($hasExternalInstitution) {
            // External co-supervisor: only core fields required
            $fields = [
                'co_supervisor_name', 'co_supervisor_title', 'co_supervisor_department',
                'co_supervisor_email', 'co_supervisor_specialization'
            ];
        } else {
            // Internal co-supervisor: all fields required except external_institution
            $fields = [
                'co_supervisor_staff_number', 'co_supervisor_name', 'co_supervisor_title',
                'co_supervisor_department', 'co_supervisor_is_coordinator', 'co_supervisor_email',
                'co_supervisor_phone', 'co_supervisor_specialization'
            ];
        }
        
        foreach ($fields as $field) {
            if (empty($row[$field])) return false;
        }
        return true;
    }
}