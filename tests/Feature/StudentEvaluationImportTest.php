<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Cache;
use App\Jobs\ImportStudentEvaluationJob;
use App\Modules\Auth\Models\User;
use App\Modules\UserManagement\Models\Role;

class StudentEvaluationImportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Queue::fake();
    }

    /** @test */
    public function office_assistant_can_upload_import_file()
    {
        // Create office assistant user
        $user = User::factory()->create();
        $role = Role::where('role_name', 'OfficeAssistant')->first();
        $user->roles()->attach($role->id);

        $this->actingAs($user);

        // Create test CSV file
        $csvContent = "student_matric_number,student_name,student_email,program_name,current_semester,student_department,evaluation_type,research_title,is_postponed,postponement_reason,main_supervisor_staff_number,main_supervisor_name,main_supervisor_title,main_supervisor_department,main_supervisor_email,main_supervisor_phone,main_supervisor_specialization,co_supervisor_staff_number,co_supervisor_name,co_supervisor_title,co_supervisor_department,co_supervisor_email,co_supervisor_phone,co_supervisor_specialization,examiner1_staff_number,examiner1_name,examiner1_title,examiner1_department,examiner1_email,examiner1_phone,examiner1_specialization,examiner2_staff_number,examiner2_name,examiner2_title,examiner2_department,examiner2_email,examiner2_phone,examiner2_specialization,examiner2_external_institution,examiner3_staff_number,examiner3_name,examiner3_title,examiner3_department,examiner3_email,examiner3_phone,examiner3_specialization,chairperson_staff_number,chairperson_name,chairperson_title,chairperson_department,chairperson_email,chairperson_phone,chairperson_specialization,nomination_status,semester,academic_year,default_password\n";
        $csvContent .= "MS2310001,Test Student,test@graduate.utm.my,Master of Computer Science,3,SEAT,FirstEvaluation,Test Research,0,,MS001,Dr. Test Research Supervisor,Dr,SEAT,supervisor@utm.my,0123456789,Test Specialization,CS001,Dr. Test Co-Supervisor,Dr,SEAT,cosupervisor@utm.my,0123456790,Test Specialization,EX001,Prof. Test Examiner1,Professor,SEAT,examiner1@utm.my,0123456791,Test Specialization,EX002,Dr. Test Examiner2,Dr,II,examiner2@utm.my,0123456792,Test Specialization,Universiti Malaya,EX003,Dr. Test Examiner3,Dr,SEAT,examiner3@utm.my,0123456793,Test Specialization,CH001,Prof. Test Chairperson,Professor,SEAT,chairperson@utm.my,0123456794,Test Specialization,Nominated,3,2023/2024,password123";

        $file = UploadedFile::fake()->createWithContent(
            'test_import.csv',
            $csvContent
        );

        $response = $this->postJson('/api/imports/upload', [
            'file' => $file
        ]);

        $response->assertStatus(202)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'import_id',
                    'data' => [
                        'import_id',
                        'filename',
                        'file_size',
                        'uploaded_at'
                    ]
                ]);

        // Assert job was dispatched
        Queue::assertPushed(ImportStudentEvaluationJob::class);
    }

    /** @test */
    public function can_get_import_status()
    {
        $user = User::factory()->create();
        $role = Role::where('role_name', 'OfficeAssistant')->first();
        $user->roles()->attach($role->id);

        $this->actingAs($user);

        $importId = 'test-import-id';
        
        // Mock cache data
        Cache::put("import_status_{$importId}", [
            'status' => 'processing',
            'message' => 'Processing import...',
            'errors' => [],
            'updated_at' => now()
        ], 3600);

        Cache::put("import_progress_{$importId}", [
            'current' => 5,
            'total' => 10,
            'progress' => 50.0,
            'message' => 'Processing row 5 of 10',
            'updated_at' => now()
        ], 3600);

        $response = $this->getJson("/api/imports/{$importId}/status");

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'import_id',
                    'status',
                    'progress'
                ]);
    }

    /** @test */
    public function can_download_template()
    {
        $user = User::factory()->create();
        $role = Role::where('role_name', 'OfficeAssistant')->first();
        $user->roles()->attach($role->id);

        $this->actingAs($user);

        $response = $this->getJson('/api/imports/template');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'template_url',
                    'filename'
                ]);
    }

    /** @test */
    public function unauthorized_user_cannot_upload_file()
    {
        $response = $this->postJson('/api/imports/upload', [
            'file' => UploadedFile::fake()->create('test.csv', 100)
        ]);

        $response->assertStatus(401);
    }

    /** @test */
    public function validates_file_type()
    {
        $user = User::factory()->create();
        $role = Role::where('role_name', 'OfficeAssistant')->first();
        $user->roles()->attach($role->id);

        $this->actingAs($user);

        $file = UploadedFile::fake()->create('test.txt', 100);

        $response = $this->postJson('/api/imports/upload', [
            'file' => $file
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['file']);
    }

    /** @test */
    public function validates_file_size()
    {
        $user = User::factory()->create();
        $role = Role::where('role_name', 'OfficeAssistant')->first();
        $user->roles()->attach($role->id);

        $this->actingAs($user);

        $file = UploadedFile::fake()->create('test.csv', 11000); // 11MB

        $response = $this->postJson('/api/imports/upload', [
            'file' => $file
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['file']);
    }
} 