<?php

namespace App\Modules\Student\Imports;
\Log::info('🚚 Importing row', $row);
use App\Modules\Student\Models\Student;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;


// docker exec -it fses_backend bash
// composer require maatwebsite/excel

// // Example Excel header and row (used with WithHeadingRow):
// -----------------------------------------------------------------------------
// matric_number | name        | email            | program_id | current_semester | department | main_supervisor_id | evaluation_type  | research_title     | is_postponed | postponement_reason
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
        \Log::info(' Importing student: ' . ($row['matric_number'] ?? 'UNKNOWN'));

        if (!\App\Models\Lecturer::where('id', $row['main_supervisor_id'] ?? null)->exists()) {
            \Log::warning(' Skipping row: Supervisor not found', $row);
            return null;
        }

        return new Student([
            'matric_number' => trim($row['matric_number'] ?? ''),
            'name' => trim($row['name'] ?? ''),
            'email' => trim($row['email'] ?? ''),
            'program_id' => $row['program_id'] ?? null,
            'current_semester' => trim($row['current_semester'] ?? ''),
            'department' => trim($row['department'] ?? ''),
            'main_supervisor_id' => $row['main_supervisor_id'] ?? null,
            'evaluation_type' => trim($row['evaluation_type'] ?? ''),
            'research_title' => trim($row['research_title'] ?? ''),
            'is_postponed' => isset($row['is_postponed']) ? (bool)$row['is_postponed'] : false,
            'postponement_reason' => trim($row['postponement_reason'] ?? ''),
        ]);
    }
}