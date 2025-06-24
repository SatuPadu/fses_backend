<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Modules\Student\Models\Student;
use App\Modules\Program\Models\Program;
use App\Modules\UserManagement\Models\Lecturer;
use App\Modules\Auth\Models\User;
use App\Modules\Evaluation\Models\Evaluation;
use App\Modules\Evaluation\Models\Supervisor;
use App\Modules\Evaluation\Models\Examiner;
use App\Modules\Evaluation\Models\Chairperson;
use App\Modules\Evaluation\Models\CoSupervisor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class StudentExportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('temp');
    }

    public function test_program_coordinator_can_export_student_data_to_excel()
    {
        // Create test data
        $program = Program::factory()->create();
        $student = Student::factory()->create(['program_id' => $program->id]);
        $evaluation = Evaluation::factory()->create(['student_id' => $student->id]);
        
        $lecturer = Lecturer::factory()->create();
        Supervisor::factory()->create([
            'student_id' => $student->id,
            'lecturer_id' => $lecturer->id
        ]);

        $user = User::factory()->create();
        $user->roles()->attach(3); // Program Coordinator role

        $response = $this->actingAs($user, 'api')
            ->postJson('/api/students/export', [
                'columns' => ['bil', 'nama', 'no_matrik', 'nama_program'],
                'format' => 'excel',
                'filters' => []
            ]);

        $response->assertStatus(200);
        $this->assertTrue(file_exists($response->getFile()->getPathname()));
    }

    public function test_pgam_can_export_student_data_to_csv()
    {
        // Create test data
        $program = Program::factory()->create();
        $student = Student::factory()->create(['program_id' => $program->id]);
        $evaluation = Evaluation::factory()->create(['student_id' => $student->id]);

        $user = User::factory()->create();
        $user->roles()->attach(4); // PGAM role

        $response = $this->actingAs($user, 'api')
            ->postJson('/api/students/export', [
                'columns' => ['bil', 'nama', 'no_matrik'],
                'format' => 'csv',
                'filters' => ['program_id' => $program->id]
            ]);

        $response->assertStatus(200);
        $this->assertTrue(file_exists($response->getFile()->getPathname()));
    }

    public function test_export_with_filters()
    {
        $program = Program::factory()->create();
        $student = Student::factory()->create([
            'program_id' => $program->id
        ]);

        $user = User::factory()->create();
        $user->roles()->attach(3); // Program Coordinator role

        $requestData = [
                'columns' => ['bil', 'nama', 'no_matrik'],
                'format' => 'excel',
                'filters' => [
                'program_id' => $program->id
                ]
        ];

        $response = $this->actingAs($user, 'api')
            ->postJson('/api/students/export', $requestData);

        $response->assertStatus(200);
    }

    public function test_get_available_columns()
    {
        $user = User::factory()->create();
        $user->roles()->attach(3); // Program Coordinator role

        $response = $this->actingAs($user, 'api')
            ->getJson('/api/students/export/columns');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'bil',
                    'nama',
                    'no_matrik',
                    'kod_program',
                    'nama_program',
                    'penyelia',
                    'penyelia_bersama_2',
                    'sem',
                    'tajuk_sebelum',
                    'pemeriksa_1',
                    'pemeriksa_2',
                    'pemeriksa_3',
                    'pengerusi',
                    'country'
                ]
            ]);
    }

    public function test_get_export_formats()
    {
        $user = User::factory()->create();
        $user->roles()->attach(3); // Program Coordinator role

        $response = $this->actingAs($user, 'api')
            ->getJson('/api/students/export/formats');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'excel',
                    'xlsx',
                    'csv',
                    'pptx'
                ]
            ]);
    }

    public function test_unauthorized_user_cannot_export()
    {
        $user = User::factory()->create();
        // No roles assigned

        $response = $this->actingAs($user, 'api')
            ->postJson('/api/students/export', [
                'columns' => ['bil', 'nama'],
                'format' => 'excel'
            ]);

        $response->assertStatus(403);
    }

    public function test_invalid_format_returns_error()
    {
        $user = User::factory()->create();
        $user->roles()->attach(3); // Program Coordinator role

        $response = $this->actingAs($user, 'api')
            ->postJson('/api/students/export', [
                'columns' => ['bil', 'nama'],
                'format' => 'invalid_format'
            ]);

        $response->assertStatus(422);
    }

    public function test_empty_columns_returns_error()
    {
        $user = User::factory()->create();
        $user->roles()->attach(3); // Program Coordinator role

        $response = $this->actingAs($user, 'api')
            ->postJson('/api/students/export', [
                'columns' => [],
                'format' => 'excel'
            ]);

        $response->assertStatus(422);
    }
} 