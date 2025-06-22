<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\Import\ImportProgressTracker;
use App\Services\Import\ImportValidator;
use App\Services\Import\ImportDataProcessor;
use Illuminate\Support\Facades\Cache;

class ImportServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    public function test_import_progress_tracker_can_update_status()
    {
        $tracker = new ImportProgressTracker('test-import-123');
        $tracker->updateStatus('processing', 'Test message');

        $status = Cache::get('import_status_test-import-123');
        
        $this->assertNotNull($status);
        $this->assertEquals('processing', $status['status']);
        $this->assertEquals('Test message', $status['message']);
    }

    public function test_import_progress_tracker_can_update_progress()
    {
        $tracker = new ImportProgressTracker('test-import-123');
        $tracker->updateProgress(5, 10, 'Processing...');

        $progress = Cache::get('import_progress_test-import-123');
        
        $this->assertNotNull($progress);
        $this->assertEquals(5, $progress['current']);
        $this->assertEquals(10, $progress['total']);
        $this->assertEquals(50, $progress['progress']);
    }

    public function test_import_validator_validates_required_fields()
    {
        $validator = new ImportValidator();
        
        $validRow = [
            'student_matric_number' => 'A123456',
            'student_name' => 'John Doe',
            'student_email' => 'john@example.com',
            'program_name' => 'Master of Computer Science',
            'current_semester' => '3',
            'student_department' => 'SEAT',
            'evaluation_type' => 'FirstEvaluation',
            'nomination_status' => 'Pending',
            'main_supervisor_staff_number' => 'STAFF001',
            'main_supervisor_name' => 'Dr. Smith',
            'main_supervisor_email' => 'smith@example.com',
            'examiner1_staff_number' => 'STAFF002',
            'examiner1_name' => 'Dr. Johnson',
            'examiner1_email' => 'johnson@example.com',
            'examiner2_staff_number' => 'STAFF003',
            'examiner2_name' => 'Dr. Brown',
            'examiner2_email' => 'brown@example.com',
            'examiner3_staff_number' => 'STAFF004',
            'examiner3_name' => 'Dr. Wilson',
            'examiner3_email' => 'wilson@example.com',
            'chairperson_staff_number' => 'STAFF005',
            'chairperson_name' => 'Dr. Davis',
            'chairperson_email' => 'davis@example.com'
        ];

        // Should not throw an exception
        $this->assertNull($validator->validateRow($validRow, 1));
    }

    public function test_import_validator_throws_exception_for_missing_fields()
    {
        $validator = new ImportValidator();
        
        $invalidRow = [
            'student_matric_number' => 'A123456',
            // Missing other required fields
        ];

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Required field 'student_name' is missing or empty");
        
        $validator->validateRow($invalidRow, 1);
    }

    public function test_import_data_processor_can_be_instantiated()
    {
        $tracker = new ImportProgressTracker('test-import-123');
        $processor = new ImportDataProcessor($tracker);
        
        $this->assertInstanceOf(ImportDataProcessor::class, $processor);
    }
} 