<?php

namespace App\Modules\Student\Imports;

use App\Models\Lecturer;
use App\Modules\Program\Models\Program; # Added Program model
use App\Modules\Student\Models\Student;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithValidation; # Added WithValidation
use Maatwebsite\Excel\Concerns\SkipsOnFailure; # Added SkipsOnFailure
use Maatwebsite\Excel\Validators\Failure; # Added Failure
use Maatwebsite\Excel\Events\AfterImport;
use Maatwebsite\Excel\Events\BeforeImport;
use Maatwebsite\Excel\Events\BeforeSheet;

// docker exec -it fses_backend bash
// composer require maatwebsite/excel

// // Example Excel header and row (used with WithHeadingRow):
// -----------------------------------------------------------------------------
// student_name | name        | email            | program_id | current_semester | department | main_supervisor_id | evaluation_type  | research_title     | is_postponed | postponement_reason
// -----------------------------------------------------------------------------
// U2100123     | Alice Smith | alice@example.com| 1          | Y1S1             | SEAT       | 5                  | FirstEvaluation  | AI in Education    | 0            | NULL
class StudentsImport implements ToCollection, WithHeadingRow, WithEvents, WithValidation, SkipsOnFailure
{
    private static ?array $existingSupervisorIds = null;
    private static ?array $existingProgramIds = null; # Added for Program IDs
    private int $successfulImports = 0;
    private int $skippedRows = 0;

    public function registerEvents(): array
    {
        return [
            BeforeImport::class => function(BeforeImport $event) {
                // Consider clearing static cache if the import instance could be long-lived
                // and process multiple files, though typically it's one instance per file.
                // self::$existingSupervisorIds = null; // Reset for a potentially new file's context
                $this->successfulImports = 0;
                $this->skippedRows = 0;
                Log::info('Starting student import process.');
            },
            AfterImport::class => function(AfterImport $event) {
                Log::info('Finished student import process.', [
                    'successful_imports' => $this->successfulImports,
                    'skipped_rows' => $this->skippedRows,
                ]);
            },
            BeforeSheet::class => function(BeforeSheet $event) {
                // Event registered as per instructions.
                // Supervisor and Program IDs are loaded lazily by their respective load methods
                // when collection() is called. This is per the "Simpler alternative for now" approach.
            }
        ];
    }

    private static function loadSupervisorIds(Collection $rows): void
    {
        if (self::$existingSupervisorIds !== null) {
            return;
        }

        $uniqueSupervisorIds = $rows->pluck('main_supervisor_id')->filter()->unique()->values()->all();
        if (!empty($uniqueSupervisorIds)) {
            self::$existingSupervisorIds = Lecturer::whereIn('id', $uniqueSupervisorIds)->pluck('id')->all();
        } else {
            self::$existingSupervisorIds = [];
        }
    }

    private static function loadProgramIds(Collection $rows): void
    {
        if (self::$existingProgramIds !== null) {
            return;
        }

        $uniqueProgramIds = $rows->pluck('program_id')->filter()->unique()->values()->all();
        if (!empty($uniqueProgramIds)) {
            self::$existingProgramIds = Program::whereIn('id', $uniqueProgramIds)->pluck('id')->all();
        } else {
            self::$existingProgramIds = [];
        }
    }

    public function rules(): array
    {
        return [
            'student_name' => 'required|string|max:255',
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:students,email', // Performance implication noted.
            'program_id' => 'required|integer', // Custom check in collection() for existence.
            'current_semester' => 'required|string|max:50',
            'department' => 'required|string|max:255',
            'main_supervisor_id' => 'required|integer', // Custom check in collection() for existence.
            'evaluation_type' => 'required|string|max:255',
            'research_title' => 'nullable|string',
            'is_postponed' => 'boolean',
            'postponement_reason' => 'nullable|string',
        ];
    }

    public function onFailure(Failure ...$failures)
    {
        foreach ($failures as $failure) {
            Log::warning('Validation failed for row, skipping.', [
                'row_number' => $failure->row(),
                'attribute' => $failure->attribute(),
                'errors' => $failure->errors(),
                'values' => $failure->values(),
            ]);
            $this->skippedRows++;
        }
    }

    /**
     * @param Collection $rows
     */
    public function collection(Collection $rows)
    {
        self::loadSupervisorIds($rows);
        self::loadProgramIds($rows); // Load Program IDs

        foreach ($rows as $row) {
            $supervisorId = $row['main_supervisor_id'] ?? null;
            $programId = $row['program_id'] ?? null;

            if (!$supervisorId || !in_array($supervisorId, self::$existingSupervisorIds)) {
                Log::warning(' Skipping row: Supervisor not found or invalid ID', ['student_name' => $row['student_name'] ?? 'UNKNOWN', 'main_supervisor_id' => $supervisorId]);
                $this->skippedRows++; // This row is skipped by custom logic, not WithValidation's onFailure.
                continue;
            }

            if (!$programId || !in_array($programId, self::$existingProgramIds)) {
                Log::warning(' Skipping row: Program ID not found or invalid ID', ['student_name' => $row['student_name'] ?? 'UNKNOWN', 'program_id' => $programId]);
                $this->skippedRows++; // This row is skipped by custom logic, not WithValidation's onFailure.
                continue;
            }

            $student = Student::create([
                'student_name' => trim($row['student_name'] ?? ''),
                'name' => trim($row['name'] ?? ''),
                'email' => trim($row['email'] ?? ''),
                'program_id' => $row['program_id'] ?? null,
                'current_semester' => trim($row['current_semester'] ?? ''),
                'department' => trim($row['department'] ?? ''),
                'main_supervisor_id' => $supervisorId,
                'evaluation_type' => trim($row['evaluation_type'] ?? ''),
                'research_title' => trim($row['research_title'] ?? ''),
                'is_postponed' => isset($row['is_postponed']) ? filter_var($row['is_postponed'], FILTER_VALIDATE_BOOLEAN) : false,
                'postponement_reason' => trim($row['postponement_reason'] ?? ''),
            ]);
            Log::debug('Successfully imported student: ' . ($student->student_name ?? 'UNKNOWN_STUDENT'), ['student_id' => $student->id]);
            $this->successfulImports++;
        }
    }
}