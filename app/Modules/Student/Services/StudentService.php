<?php

namespace App\Modules\Student\Services;

use App\Modules\Student\Models\Student;
use App\Modules\Student\Imports\StudentsImport;
use App\Modules\Lecturer\Models\Lecturer;
use Illuminate\Http\UploadedFile;
use Maatwebsite\Excel\Facades\Excel;

class StudentService
{

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
     * Retrieve paginated and optionally filtered list of students.
     *
     * Role-based filtering behavior:
     * - PGAM: Can view all students
     * - PC (Program Coordinator): Can view only students from their department
     * - RS (Research Supervisor): Can view only students they supervise
     *
     * @param int $numPerPage
     * @param array $filters
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getStudents(int $numPerPage, array $filters)
    {
        $query = Student::query();

        // Apply filter by program if provided
        if (isset($filters['program'])) {
            $query->where('program_id', $filters['program']);
        }

        if (isset($filters['student_id'])) {
            $query->where('student_id', 'like', '%' . $filters['student_id'] . '%');
        }

        if (isset($filters['name'])) {
            $query->where('name', 'like', '%' . $filters['name'] . '%');
        }

        $user = auth()->user();
        $role = $user->roles->pluck('role_name')->first();

        switch ($role) {
            case 'RS':
                $query->where('main_supervisor_id', $user->lecturer_id);
                break;
            case 'PC':
                $query->where('department', $user->department);
                break;
            case 'PGAM':
            default:
                // No additional filter
                break;
        }

        return $query->paginate($numPerPage);
    }

    /**
     * Create a new student record.
     *
     * @param array $data
     * @return Student
     */
    public function createStudent(array $data): Student
    {
        if (Student::where('student_id', $data['student_id'])->exists()) {
            throw new \Exception('Student ID already exists.');
        }

        Lecturer::findOrFail($data['main_supervisor_id']);

        return Student::create($data);
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