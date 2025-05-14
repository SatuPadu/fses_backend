<?php

namespace App\Modules\Student\Imports;

use App\Modules\Student\Models\Student;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;


// docker exec -it fses_backend bash
// composer require maatwebsite/excel

// // Example Excel header and row (used with WithHeadingRow):
// -----------------------------------------------------------------------------
// student_name | name        | email            | program_id | current_semester | department | main_supervisor_id | evaluation_type  | research_title     | is_postponed | postponement_reason
// -----------------------------------------------------------------------------
// U2100123     | Alice Smith | alice@example.com| 1          | Y1S1             | SEAT       | 5                  | FirstEvaluation  | AI in Education    | 0            | NULL
class StudentsImport implements ToModel, WithHeadingRow
{
    /**
     * @param array $row
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function model(array $row)
    {
        return new Student([
            'student_name' => $row['student_name'] ?? '',
            'name' => $row['name'] ?? '',
            'email' => $row['email'] ?? '',
            'program_id' => $row['program_id'] ?? null,
            'current_semester' => $row['current_semester'] ?? '',
            'department' => $row['department'] ?? '',
            'main_supervisor_id' => $row['main_supervisor_id'] ?? null,
            'evaluation_type' => $row['evaluation_type'] ?? '',
            'research_title' => $row['research_title'] ?? null,
            'is_postponed' => isset($row['is_postponed']) ? (bool)$row['is_postponed'] : false,
            'postponement_reason' => $row['postponement_reason'] ?? null,
        ]);
    }
}