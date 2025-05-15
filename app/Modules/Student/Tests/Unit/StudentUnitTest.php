<?php

namespace App\Modules\Student\Tests\Unit;

use App\Modules\Student\Models\Student;
use App\Modules\Student\Repositories\StudentRepository;
use App\Modules\Student\Services\StudentService;
use Illuminate\Http\UploadedFile;
use Mockery;
use Tests\TestCase;

class StudentUnitTest extends TestCase
{
    protected StudentService $studentService;
    protected $studentRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->studentRepository = Mockery::mock([StudentRepository::class]);
        $this->studentService = new StudentService($this->studentRepository);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function it_creates_a_student_successfully()
    {
        $data = [
            'student_name' => 'U2100123',
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

        $student = new Student($data);

        $this->studentRepository
            ->shouldReceive('create')
            ->once()
            ->with($data)
            ->andReturn($student);

        $result = $this->studentService->createStudent($data);

        $this->assertEquals('Alice Smith', $result->name);
    }

    /** @test */
    public function it_throws_an_error_when_student_name_is_duplicate()
    {
        $data = [
            'student_name' => 'U2100123',
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

        $this->studentRepository
            ->shouldReceive('create')
            ->once()
            ->with($data)
            ->andThrow(new \Exception('Duplicate student name'));

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Duplicate student name');

        $this->studentService->createStudent($data);
    }
}