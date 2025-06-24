<?php

namespace App\Modules\Student\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentFeatureTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_fetch_students()
    {
        $response = $this->getJson('/api/students');

        $response->assertStatus(200);
    }

    /** @test */
    public function it_can_create_a_student_with_valid_data()
    {
        $data = [
            'matric_number' => 'U2100123',
            'name' => 'Alice Smith',
            'email' => 'alice@example.com',
            'program_id' => 1,
            'current_semester' => 'Y1S1',
            'department' => 'SEAT',
            'main_supervisor_id' => 1,
            'evaluation_type' => 'FirstEvaluation',
            'research_title' => 'AI in Education',
            'is_postponed' => false,
            'postponement_reason' => null,
        ];

        $response = $this->postJson('/api/students', $data);

        $response->assertStatus(200)
                 ->assertJsonFragment(['matric_number' => 'U2100123']);
    }

    /** @test */
    public function it_can_import_students_from_excel_file()
    {
        // Copy a test Excel file into the correct location for testing
        $path = base_path('tests/files/students_import_sample.xlsx');

        // Simulate file upload
        $file = new \Illuminate\Http\UploadedFile(
            $path,
            'students_import_sample.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true
        );

        $response = $this->postJson('/api/students/import', ['file' => $file]);

        $response->assertStatus(200)
                 ->assertJsonFragment(['message' => 'Students imported successfully.']);    
    }
}