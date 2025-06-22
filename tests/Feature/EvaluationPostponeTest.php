<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Modules\Auth\Models\User;
use App\Modules\Student\Models\Student;
use App\Modules\Evaluation\Models\Evaluation;
use App\Modules\UserManagement\Models\Lecturer;
use App\Modules\UserManagement\Models\Role;
use App\Enums\NominationStatus;
use App\Enums\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use App\Mail\EvaluationPostponedMail;

class EvaluationPostponeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
    }

    /** @test */
    public function supervisor_can_postpone_evaluation()
    {
        // Create roles
        $supervisorRole = Role::factory()->create(['role_name' => UserRole::SUPERVISOR]);
        
        // Create supervisor user
        $supervisor = User::factory()->create([
            'staff_number' => 'SUP001',
            'email' => 'supervisor@test.com'
        ]);
        $supervisor->roles()->attach($supervisorRole->id);

        // Create lecturer for supervisor
        Lecturer::factory()->create([
            'staff_number' => 'SUP001',
            'name' => 'Test Supervisor'
        ]);

        // Create student
        $student = Student::factory()->create([
            'main_supervisor_id' => 1
        ]);

        // Create evaluation
        $evaluation = Evaluation::factory()->create([
            'student_id' => $student->id,
            'nomination_status' => NominationStatus::NOMINATED,
            'examiner1_id' => 1,
            'examiner2_id' => 1,
            'examiner3_id' => 1,
            'semester' => 1,
            'academic_year' => '2024/2025'
        ]);

        $response = $this->actingAs($supervisor)
            ->postJson("/api/evaluations/nominations/{$evaluation->id}/postpone", [
                'reason' => 'Student requested additional time',
                'postponed_to' => '2025-02-15'
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Nomination postponed successfully!'
            ]);

        // Check database
        $this->assertDatabaseHas('student_evaluations', [
            'id' => $evaluation->id,
            'nomination_status' => NominationStatus::POSTPONED,
            'is_postponed' => true,
            'postponement_reason' => 'Student requested additional time',
            'postponed_to' => '2025-02-15 00:00:00'
        ]);

        // Check email was sent
        Mail::assertSent(EvaluationPostponedMail::class);
    }

    /** @test */
    public function non_supervisor_cannot_postpone_evaluation()
    {
        // Create office assistant role
        $officeAssistantRole = Role::factory()->create(['role_name' => UserRole::OFFICE_ASSISTANT]);
        
        // Create office assistant user
        $officeAssistant = User::factory()->create([
            'staff_number' => 'OA001',
            'email' => 'assistant@test.com'
        ]);
        $officeAssistant->roles()->attach($officeAssistantRole->id);

        // Create evaluation
        $evaluation = Evaluation::factory()->create([
            'nomination_status' => NominationStatus::NOMINATED
        ]);

        $response = $this->actingAs($officeAssistant)
            ->postJson("/api/evaluations/nominations/{$evaluation->id}/postpone", [
                'reason' => 'Test reason',
                'postponed_to' => '2025-02-15'
            ]);

        $response->assertStatus(403);
    }

    /** @test */
    public function reason_is_required_for_postponement()
    {
        // Create supervisor
        $supervisorRole = Role::factory()->create(['role_name' => UserRole::SUPERVISOR]);
        $supervisor = User::factory()->create(['staff_number' => 'SUP001']);
        $supervisor->roles()->attach($supervisorRole->id);

        // Create evaluation
        $evaluation = Evaluation::factory()->create([
            'nomination_status' => NominationStatus::NOMINATED
        ]);

        $response = $this->actingAs($supervisor)
            ->postJson("/api/evaluations/nominations/{$evaluation->id}/postpone", [
                'postponed_to' => '2025-02-15'
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['reason']);
    }

    /** @test */
    public function postponed_to_must_be_future_date()
    {
        // Create supervisor
        $supervisorRole = Role::factory()->create(['role_name' => UserRole::SUPERVISOR]);
        $supervisor = User::factory()->create(['staff_number' => 'SUP001']);
        $supervisor->roles()->attach($supervisorRole->id);

        // Create evaluation
        $evaluation = Evaluation::factory()->create([
            'nomination_status' => NominationStatus::NOMINATED
        ]);

        $response = $this->actingAs($supervisor)
            ->postJson("/api/evaluations/nominations/{$evaluation->id}/postpone", [
                'reason' => 'Test reason',
                'postponed_to' => '2020-01-01'
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['postponed_to']);
    }

    /** @test */
    public function evaluation_must_exist_for_postponement()
    {
        // Create supervisor
        $supervisorRole = Role::factory()->create(['role_name' => UserRole::SUPERVISOR]);
        $supervisor = User::factory()->create(['staff_number' => 'SUP001']);
        $supervisor->roles()->attach($supervisorRole->id);

        $response = $this->actingAs($supervisor)
            ->postJson("/api/evaluations/nominations/999/postpone", [
                'reason' => 'Test reason',
                'postponed_to' => '2025-02-15'
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['evaluation_id']);
    }
} 