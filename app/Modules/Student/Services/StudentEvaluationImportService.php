<?php

namespace App\Modules\Student\Services;

use App\Modules\Program\Models\Program;
use App\Modules\UserManagement\Models\Lecturer;
use App\Modules\Auth\Models\User;
use App\Modules\Student\Models\Student;
use App\Modules\Evaluation\Models\Evaluation;
use App\Enums\Department;
use App\Enums\LecturerTitle;
use App\Enums\EvaluationType;
use App\Enums\NominationStatus;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class StudentEvaluationImportService
{
    /**
     * Validate a single row of import data
     */
    public function validateRow(array $row, int $rowNumber): array
    {
        $errors = [];

        // Required fields validation
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
                $errors[] = "Required field '{$field}' is missing or empty";
            }
        }

        // Email validation
        $emailFields = [
            'student_email', 'main_supervisor_email', 'co_supervisor_email',
            'examiner1_email', 'examiner2_email', 'examiner3_email', 'chairperson_email'
        ];

        foreach ($emailFields as $field) {
            if (!empty($row[$field]) && !filter_var($row[$field], FILTER_VALIDATE_EMAIL)) {
                $errors[] = "Invalid email format for '{$field}': {$row[$field]}";
            }
        }

        // Enum validation
        if (!empty($row['student_department']) && !Department::isValid($row['student_department'])) {
            $errors[] = "Invalid department: {$row['student_department']}";
        }

        if (!empty($row['evaluation_type']) && !EvaluationType::isValid($row['evaluation_type'])) {
            $errors[] = "Invalid evaluation type: {$row['evaluation_type']}";
        }

        if (!empty($row['nomination_status']) && !NominationStatus::isValid($row['nomination_status'])) {
            $errors[] = "Invalid nomination status: {$row['nomination_status']}";
        }

        // Lecturer title validation
        $lecturerTitles = [
            'main_supervisor_title', 'co_supervisor_title', 'examiner1_title',
            'examiner2_title', 'examiner3_title', 'chairperson_title'
        ];

        foreach ($lecturerTitles as $titleField) {
            if (!empty($row[$titleField]) && !LecturerTitle::isValid($row[$titleField])) {
                $errors[] = "Invalid lecturer title '{$row[$titleField]}' for {$titleField}";
            }
        }

        // Business rule validation
        $this->validateBusinessRules($row, $errors);

        return $errors;
    }

    /**
     * Validate business rules for examiner and chairperson assignments
     */
    protected function validateBusinessRules(array $row, array &$errors): void
    {
        // Examiner 1 must be at least Associate Professor
        if (!empty($row['examiner1_title'])) {
            if (!in_array($row['examiner1_title'], [LecturerTitle::PROFESSOR_MADYA, LecturerTitle::PROFESSOR])) {
                $errors[] = "Examiner 1 must be at least Associate Professor (ProfessorMadya)";
            }
        }

        // If main supervisor is Professor, Examiner 1 must be Professor
        if (!empty($row['main_supervisor_title']) && $row['main_supervisor_title'] === LecturerTitle::PROFESSOR) {
            if (!empty($row['examiner1_title']) && $row['examiner1_title'] !== LecturerTitle::PROFESSOR) {
                $errors[] = "If main supervisor is Professor, Examiner 1 must also be Professor";
            }
        }

        // Chairperson must be at least Associate Professor
        if (!empty($row['chairperson_title'])) {
            if (!in_array($row['chairperson_title'], [LecturerTitle::PROFESSOR_MADYA, LecturerTitle::PROFESSOR])) {
                $errors[] = "Chairperson must be at least Associate Professor (ProfessorMadya)";
            }
        }

        // If main supervisor is Professor, chairperson must be Professor
        if (!empty($row['main_supervisor_title']) && $row['main_supervisor_title'] === LecturerTitle::PROFESSOR) {
            if (!empty($row['chairperson_title']) && $row['chairperson_title'] !== LecturerTitle::PROFESSOR) {
                $errors[] = "If main supervisor is Professor, chairperson must also be Professor";
            }
        }

        // If any examiner is Professor, chairperson must be Professor
        $examinerTitles = [
            $row['examiner1_title'] ?? '',
            $row['examiner2_title'] ?? '',
            $row['examiner3_title'] ?? ''
        ];

        if (in_array(LecturerTitle::PROFESSOR, $examinerTitles)) {
            if (!empty($row['chairperson_title']) && $row['chairperson_title'] !== LecturerTitle::PROFESSOR) {
                $errors[] = "If any examiner is Professor, chairperson must also be Professor";
            }
        }

        // Check for duplicate assignments (same person cannot be supervisor, examiner, and chair)
        $this->validateDuplicateAssignments($row, $errors);
    }

    /**
     * Validate that the same person is not assigned multiple roles
     */
    protected function validateDuplicateAssignments(array $row, array &$errors): void
    {
        $emails = [
            'main_supervisor' => $row['main_supervisor_email'] ?? '',
            'co_supervisor' => $row['co_supervisor_email'] ?? '',
            'examiner1' => $row['examiner1_email'] ?? '',
            'examiner2' => $row['examiner2_email'] ?? '',
            'examiner3' => $row['examiner3_email'] ?? '',
            'chairperson' => $row['chairperson_email'] ?? ''
        ];

        $emailCounts = array_count_values(array_filter($emails));
        
        foreach ($emailCounts as $email => $count) {
            if ($count > 1) {
                $roles = array_keys(array_filter($emails, function($value) use ($email) {
                    return $value === $email;
                }));
                $errors[] = "Email {$email} is assigned to multiple roles: " . implode(', ', $roles);
            }
        }
    }

    /**
     * Get program mapping for different program types
     */
    public function getProgramMapping(): array
    {
        return [
            'Master of Computer Science' => [
                'code' => 'MCS',
                'total_semesters' => 4,
                'evaluation_semester' => 3
            ],
            'Master of Information Technology' => [
                'code' => 'MIT',
                'total_semesters' => 4,
                'evaluation_semester' => 3
            ],
            'Doctor of Philosophy' => [
                'code' => 'PhD',
                'total_semesters' => 8,
                'evaluation_semester' => 3
            ],
            'Master of Philosophy' => [
                'code' => 'MPhil',
                'total_semesters' => 4,
                'evaluation_semester' => 2
            ],
            'Doctor of Software Engineering' => [
                'code' => 'DSE',
                'total_semesters' => 8,
                'evaluation_semester' => 8
            ]
        ];
    }

    /**
     * Check if lecturer is external (only examiner 2 can be external)
     */
    public function isExternalLecturer(string $type, array $row): bool
    {
        if ($type === 'examiner2') {
            return !empty($row['examiner2_external_institution']);
        }
        return false;
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
            'programs_created' => 0,
            'lecturers_created' => 0,
            'students_created' => 0,
            'evaluations_created' => 0,
            'users_created' => 0,
            'errors' => []
        ];

        foreach ($results as $result) {
            if ($result['success']) {
                $summary['successful']++;
                $summary['programs_created'] += $result['programs_created'] ?? 0;
                $summary['lecturers_created'] += $result['lecturers_created'] ?? 0;
                $summary['students_created'] += $result['students_created'] ?? 0;
                $summary['evaluations_created'] += $result['evaluations_created'] ?? 0;
                $summary['users_created'] += $result['users_created'] ?? 0;
            } else {
                $summary['failed']++;
                $summary['errors'][] = $result['error'];
            }
        }

        return $summary;
    }
} 