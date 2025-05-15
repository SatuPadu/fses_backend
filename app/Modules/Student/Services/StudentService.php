<?php

namespace App\Modules\Student\Services;

use App\Modules\Student\Models\Student;
use App\Modules\Student\Imports\StudentsImport;
use Illuminate\Http\UploadedFile;
use Maatwebsite\Excel\Facades\Excel;
use App\Modules\Student\Repositories\StudentRepository;

class StudentService
{
    private StudentRepository $studentRepository;

    public function __construct(StudentRepository $studentRepository)
    {
        $this->studentRepository = $studentRepository;
    }

    /**
     * Retrieve all students, optionally filtered by program and user role.
     *
     * Role-based filtering behavior:
     * - PGAM: Can view all students
     * - PC (Program Coordinator): Can view only students from their department
     * - RS (Research Supervisor): Can view only students they supervise (main_supervisor_id matches)
     *
     * @param array $filters Optional filters like 'program'
     * @return \Illuminate\Support\Collection
     */
    public function getAllStudents(array $filters = [])
    {
        $query = Student::query();

        // Apply filter by program if provided
        if (isset($filters['program'])) {
            $query->where('program_id', $filters['program']);
        }

        $user = auth()->user();
        $role = $user->roles->pluck('role_name')->first();

        // Role-based student visibility
        switch ($role) {
            case 'RS': // Research Supervisor
                $query->where('main_supervisor_id', $user->lecturer_id);
                break;
            case 'PC': // Program Coordinator
                $query->where('department', $user->department);
                break;
            case 'PGAM':
            default:
                // PGAM or unknown role sees all students
                break;
        }

        return $query->get();
    }

    /**
     * Create a new student record.
     *
     * @param array $data
     * @return Student
     */
    public function createStudent(array $data): Student
    {
        return $this->studentRepository->create($data);
    }

    /**
     * Import students in bulk from an Excel file.
     *
     * @param UploadedFile $file
     * @return void
     */
    public function importFromExcel(UploadedFile $file): void
    {
        Excel::import(new StudentsImport, $file);
    }
}