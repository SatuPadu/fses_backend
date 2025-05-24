<?php

namespace App\Modules\Student\Tests\Unit;

use App\Modules\Program\Models\Program;
use App\Modules\Student\Imports\StudentsImport;
use App\Modules\Student\Models\Student;
use App\Models\Lecturer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Validators\Failure;
use Tests\TestCase;
use Illuminate\Support\Facades\Event;
use Maatwebsite\Excel\Events\BeforeImport;
use Maatwebsite\Excel\Events\AfterImport;


class StudentImportTest extends TestCase
{
    use RefreshDatabase;

    protected Lecturer $lecturer;
    protected Program $program;
    protected StudentsImport $importer;

    protected function setUp(): void
    {
        parent::setUp();

        // Create default lecturer and program for tests
        $this->lecturer = Lecturer::create(['name' => 'Dr. Supervisor', 'email' => 'supervisor@example.com']);
        $this->program = Program::create(['name' => 'Software Engineering', 'department_id' => 1]); // Assuming department_id or similar exists

        $this->importer = new StudentsImport();

        // It's good practice to ensure events are not faked unless specified by a test
        Event::fake([]);
    }

    protected function tearDown(): void
    {
        // Reset static properties to avoid interference between tests
        $reflection = new \ReflectionClass(StudentsImport::class);

        $properties = ['existingSupervisorIds', 'existingProgramIds'];
        foreach ($properties as $propertyName) {
            if ($reflection->hasProperty($propertyName)) {
                $prop = $reflection->getProperty($propertyName);
                $prop->setAccessible(true);
                $prop->setValue(null, null);
            }
        }
        parent::tearDown();
    }

    private function createRowData(array $overrides = []): array
    {
        return array_merge([
            'student_name' => 'U2000001',
            'name' => 'Test Student',
            'email' => 'test.student@example.com',
            'program_id' => $this->program->id,
            'current_semester' => 'Y1S1',
            'department' => 'SEAT', // Example department
            'main_supervisor_id' => $this->lecturer->id,
            'evaluation_type' => 'FirstEvaluation',
            'research_title' => 'AI in Testing',
            'is_postponed' => false,
            'postponement_reason' => null,
        ], $overrides);
    }

    public function test_successful_student_import()
    {
        Log::spy();
        $rowData = $this->createRowData();
        $rows = new Collection([$rowData]);

        $this->importer->collection($rows);

        $this->assertDatabaseHas('students', [
            'student_name' => 'U2000001',
            'email' => 'test.student@example.com',
            'program_id' => $this->program->id,
            'main_supervisor_id' => $this->lecturer->id,
        ]);

        // Access private properties for assertion (if necessary and no other way)
        $reflection = new \ReflectionClass($this->importer);
        $successfulImportsProp = $reflection->getProperty('successfulImports');
        $successfulImportsProp->setAccessible(true);
        $this->assertEquals(1, $successfulImportsProp->getValue($this->importer));

        $skippedRowsProp = $reflection->getProperty('skippedRows');
        $skippedRowsProp->setAccessible(true);
        $this->assertEquals(0, $skippedRowsProp->getValue($this->importer));

        Log::shouldHaveReceived('debug')->once()->with(\Mockery::pattern('/Successfully imported student/'));
    }

    public function test_skip_student_if_supervisor_id_invalid()
    {
        Log::spy();
        $rowData = $this->createRowData(['main_supervisor_id' => 9999]); // Non-existent supervisor
        $rows = new Collection([$rowData]);

        $this->importer->collection($rows);

        $this->assertDatabaseMissing('students', ['email' => $rowData['email']]);
        
        $reflection = new \ReflectionClass($this->importer);
        $skippedRowsProp = $reflection->getProperty('skippedRows');
        $skippedRowsProp->setAccessible(true);
        $this->assertEquals(1, $skippedRowsProp->getValue($this->importer));

        Log::shouldHaveReceived('warning')->once()->with(
            ' Skipping row: Supervisor not found or invalid ID',
            \Mockery::on(function ($data) use ($rowData) {
                return $data['student_name'] === $rowData['student_name'] && $data['main_supervisor_id'] === 9999;
            })
        );
    }

    public function test_skip_student_if_program_id_invalid()
    {
        Log::spy();
        $rowData = $this->createRowData(['program_id' => 8888]); // Non-existent program
        $rows = new Collection([$rowData]);

        $this->importer->collection($rows);

        $this->assertDatabaseMissing('students', ['email' => $rowData['email']]);

        $reflection = new \ReflectionClass($this->importer);
        $skippedRowsProp = $reflection->getProperty('skippedRows');
        $skippedRowsProp->setAccessible(true);
        $this->assertEquals(1, $skippedRowsProp->getValue($this->importer));
        
        Log::shouldHaveReceived('warning')->once()->with(
            ' Skipping row: Program ID not found or invalid ID',
            \Mockery::on(function ($data) use ($rowData) {
                return $data['student_name'] === $rowData['student_name'] && $data['program_id'] === 8888;
            })
        );
    }

    // Helper function to create a temporary CSV file for import testing
    protected function createTemporaryCsv(array $header, array $rowsData): string
    {
        $path = storage_path('app/temp_import_test.csv');
        $file = fopen($path, 'w');

        fputcsv($file, $header);
        foreach ($rowsData as $row) {
            fputcsv($file, $row);
        }
        fclose($file);
        return $path;
    }

    public function test_validation_failure_invalid_email()
    {
        Log::spy();
        // Prepare data with an invalid email
        $invalidRowData = $this->createRowData(['email' => 'not-an-email']);
        
        // Define header based on createRowData keys
        $header = array_keys($this->createRowData());
        $filePath = $this->createTemporaryCsv($header, [$invalidRowData]);

        // Instantiate a new importer for this test to ensure fresh counters if Excel::import uses a shared instance
        $importer = new StudentsImport();
        Excel::import($importer, $filePath);

        $this->assertDatabaseMissing('students', ['student_name' => $invalidRowData['student_name']]);

        $reflection = new \ReflectionClass($importer);
        $skippedRowsProp = $reflection->getProperty('skippedRows');
        $skippedRowsProp->setAccessible(true);
        // This will be 1 because onFailure increments it
        $this->assertEquals(1, $skippedRowsProp->getValue($importer)); 

        Log::shouldHaveReceived('warning')->once()->with(
            'Validation failed for row, skipping.',
            \Mockery::on(function ($data) {
                return $data['attribute'] === 'email' && !empty($data['errors']);
            })
        );
        unlink($filePath); // Clean up
    }

    public function test_email_uniqueness_validation()
    {
        Log::spy();
        // 1. Create an existing student
        $existingStudentEmail = 'unique.email@example.com';
        Student::create($this->createRowData(['email' => $existingStudentEmail, 'student_name' => 'UEXISTING']));

        // 2. Prepare data for a new student with the same email
        $newRowData = $this->createRowData(['email' => $existingStudentEmail, 'student_name' => 'UNEW']);
        $header = array_keys($this->createRowData());
        $filePath = $this->createTemporaryCsv($header, [$newRowData]);
        
        $importer = new StudentsImport();
        Excel::import($importer, $filePath);

        // Assert the new student (UNEW) was not created
        $this->assertDatabaseMissing('students', ['student_name' => 'UNEW']);
        // Assert the original student (UEXISTING) still exists
        $this->assertDatabaseHas('students', ['student_name' => 'UEXISTING']);

        $reflection = new \ReflectionClass($importer);
        $skippedRowsProp = $reflection->getProperty('skippedRows');
        $skippedRowsProp->setAccessible(true);
        $this->assertEquals(1, $skippedRowsProp->getValue($importer));

        Log::shouldHaveReceived('warning')->once()->with(
            'Validation failed for row, skipping.',
            \Mockery::on(function ($data) {
                 return $data['attribute'] === 'email' && 
                        is_array($data['errors']) && 
                        str_contains($data['errors'][0], 'taken');
            })
        );
        unlink($filePath); // Clean up
    }

    public function test_event_logging_and_counters()
    {
        Log::spy();
        // DO NOT fake BeforeImport and AfterImport here if we want their listeners (which do logging) to run.
        // Event::fake([BeforeImport::class, AfterImport::class]); // Keep this commented or remove

        // Prepare data: 1 valid, 1 invalid supervisor, 1 invalid program, 1 invalid email
        $validRow = $this->createRowData(['email' => 'valid1@example.com', 'student_name' => 'VALIDSTUDENT']);
        $invalidSupervisorRow = $this->createRowData(['main_supervisor_id' => 9998, 'email' => 'invalid.supervisor@example.com', 'student_name' => 'INVALIDSUP']);
        $invalidProgramRow = $this->createRowData(['program_id' => 8887, 'email' => 'invalid.program@example.com', 'student_name' => 'INVALIDPROG']);
        $invalidEmailRow = $this->createRowData(['email' => 'not-a-valid-email', 'student_name' => 'INVALIDEMAIL']);

        $allRowsData = [$validRow, $invalidSupervisorRow, $invalidProgramRow, $invalidEmailRow];
        $header = array_keys($this->createRowData());
        $filePath = $this->createTemporaryCsv($header, $allRowsData);

        $importer = new StudentsImport(); // Fresh importer
        Excel::import($importer, $filePath);

        // Assertions on counters from the importer instance
        $reflection = new \ReflectionClass($importer);
        
        $successfulImportsProp = $reflection->getProperty('successfulImports');
        $successfulImportsProp->setAccessible(true);
        $this->assertEquals(1, $successfulImportsProp->getValue($importer), "Successful imports count mismatch.");

        $skippedRowsProp = $reflection->getProperty('skippedRows');
        $skippedRowsProp->setAccessible(true);
        // 1 (invalid supervisor) + 1 (invalid program) + 1 (invalid email from WithValidation) = 3
        $this->assertEquals(3, $skippedRowsProp->getValue($importer), "Skipped rows count mismatch.");

        // Check specific log messages
        Log::shouldHaveReceived('debug')->with(\Mockery::pattern('/Successfully imported student: VALIDSTUDENT/'));
        Log::shouldHaveReceived('warning')->with(' Skipping row: Supervisor not found or invalid ID', \Mockery::on(fn($data) => $data['student_name'] === 'INVALIDSUP'));
        Log::shouldHaveReceived('warning')->with(' Skipping row: Program ID not found or invalid ID', \Mockery::on(fn($data) => $data['student_name'] === 'INVALIDPROG'));
        Log::shouldHaveReceived('warning')->with('Validation failed for row, skipping.', \Mockery::on(fn($data) => $data['values']['student_name'] === 'INVALIDEMAIL' && $data['attribute'] === 'email'));
        
        // Check specific log messages
        Log::shouldHaveReceived('info')->once()->with('Starting student import process.');
        Log::shouldHaveReceived('info')->once()->with(
            'Finished student import process.',
            \Mockery::on(function ($data) {
                return $data['successful_imports'] === 1 && $data['skipped_rows'] === 3;
            })
        );

        Log::shouldHaveReceived('debug')->with(\Mockery::pattern('/Successfully imported student: VALIDSTUDENT/'));
        Log::shouldHaveReceived('warning')->with(' Skipping row: Supervisor not found or invalid ID', \Mockery::on(fn($data) => $data['student_name'] === 'INVALIDSUP'));
        Log::shouldHaveReceived('warning')->with(' Skipping row: Program ID not found or invalid ID', \Mockery::on(fn($data) => $data['student_name'] === 'INVALIDPROG'));
        Log::shouldHaveReceived('warning')->with('Validation failed for row, skipping.', \Mockery::on(fn($data) => $data['values']['student_name'] === 'INVALIDEMAIL' && $data['attribute'] === 'email'));
        
        unlink($filePath); // Clean up
    }
}
