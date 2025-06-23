<?php

namespace App\Modules\Student\Services;

use App\Modules\Student\Models\Student;
use App\Modules\Student\Imports\StudentsImport;
use App\Modules\UserManagement\Models\Lecturer;
use Illuminate\Http\UploadedFile;
use Maatwebsite\Excel\Facades\Excel;

class StudentService
{
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
    public function getAllStudents(int $numPerPage, array $filters)
    {
        $query = Student::with(['program', 'mainSupervisor']);

        // Apply filter by program if provided
        if (isset($filters['program'])) {
            $query->where('program_id', $filters['program']);
        }

        if (isset($filters['matric_number'])) {
            $query->where('matric_number', 'like', '%' . $filters['matric_number'] . '%');
        }

        if (isset($filters['name'])) {
            $query->where('name', 'like', '%' . $filters['name'] . '%');
        }

        if (isset($filters['department'])) {
            $query->where('department', $filters['department']);
        }

        if (isset($filters['evaluation_type'])) {
            $query->where('evaluation_type', $filters['evaluation_type']);
        }

        if (isset($filters['is_postponed'])) {
            $query->where('is_postponed', $filters['is_postponed']);
        }

        if (isset($filters['supervisor_id'])) {
            $query->where('main_supervisor_id', $filters['supervisor_id']);
        }

        // Apply role-based filtering
        $user = auth()->user();
        $userRoles = $user->roles->pluck('role_name')->toArray();

        // Check if user has PGAM role (can see all data)
        if (in_array('PGAM', $userRoles)) {
            // PGAM can see all data - no additional filtering needed
        }
        // Check if user has Office Assistant role (can see all data)
        elseif (in_array('OfficeAssistant', $userRoles)) {
            // Office Assistant can see all data - no additional filtering needed
        }
        // Check if user is a Program Coordinator (can only see their department)
        elseif (in_array('ProgramCoordinator', $userRoles)) {
            $query->where('department', $user->department);
        }
        // Check if user is a Supervisor (can only see their supervised students)
        elseif (in_array('Supervisor', $userRoles)) {
            $query->whereHas('mainSupervisor', function ($q) use ($user) {
                $q->where('staff_number', $user->staff_number);
            });
        }
        // Check if user is a Chairperson (can only see students they're chairing)
        elseif (in_array('Chairperson', $userRoles)) {
            $query->whereHas('evaluations', function ($q) use ($user) {
                $q->whereHas('chairperson', function ($cQ) use ($user) {
                    $cQ->where('staff_number', $user->staff_number);
                });
            });
        }
        // Default: no access (empty result)
        else {
            $query->whereRaw('1 = 0'); // This will return no results
        }

        return $query->orderBy('created_at', 'desc')->paginate($numPerPage);
    }

    /**
     * Create a new student record with role-based access control.
     *
     * @param array $data
     * @return Student
     * @throws \Exception
     */
    public function createStudent(array $data): Student
    {
        // Apply role-based access control
        $user = auth()->user();
        $userRoles = $user->roles->pluck('role_name')->toArray();

        // Only PGAM and Office Assistant can create students
        if (!in_array('PGAM', $userRoles) && !in_array('OfficeAssistant', $userRoles)) {
            throw new \Exception('Access denied. Only PGAM and Office Assistant can create student records.');
        }

        if (Student::where('matric_number', $data['matric_number'])->exists()) {
            throw new \Exception('Student matric number already exists.');
        }

        Lecturer::findOrFail($data['main_supervisor_id']);

        return Student::create($data);
    }



    /**
     * Get a specific student by ID with role-based access control.
     *
     * @param int $id
     * @return Student
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     * @throws \Exception
     */
    public function getStudentById(int $id): Student
    {
        $student = Student::with(['program', 'mainSupervisor'])->findOrFail($id);
        
        // Apply role-based access control
        $user = auth()->user();
        $userRoles = $user->roles->pluck('role_name')->toArray();

        // Check if user has PGAM role (can see all data)
        if (in_array('PGAM', $userRoles)) {
            return $student;
        }
        // Check if user has Office Assistant role (can see all data)
        elseif (in_array('OfficeAssistant', $userRoles)) {
            return $student;
        }
        // Check if user is a Program Coordinator (can only see their department)
        elseif (in_array('ProgramCoordinator', $userRoles)) {
            if ($student->department !== $user->department) {
                throw new \Exception('Access denied. You can only view students from your department.');
            }
            return $student;
        }
        // Check if user is a Supervisor (can only see their supervised students)
        elseif (in_array('Supervisor', $userRoles)) {
            if ($student->mainSupervisor->staff_number !== $user->staff_number) {
                throw new \Exception('Access denied. You can only view students you supervise.');
            }
            return $student;
        }
        // Check if user is a Chairperson (can only see students they're chairing)
        elseif (in_array('Chairperson', $userRoles)) {
            $isChairperson = $student->evaluations->some(function ($evaluation) use ($user) {
                return $evaluation->chairperson && $evaluation->chairperson->staff_number === $user->staff_number;
            });
            
            if (!$isChairperson) {
                throw new \Exception('Access denied. You can only view students you are chairing.');
            }
            return $student;
        }
        // Default: no access
        else {
            throw new \Exception('Access denied. You do not have permission to view this student.');
        }
    }

    /**
     * Update a student record with role-based access control.
     *
     * @param int $id
     * @param array $data
     * @return Student
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     * @throws \Exception
     */
    public function updateStudent(int $id, array $data): Student
    {
        $student = Student::findOrFail($id);
        
        // Apply role-based access control
        $user = auth()->user();
        $userRoles = $user->roles->pluck('role_name')->toArray();

        // Only PGAM and Office Assistant can update students
        if (!in_array('PGAM', $userRoles) && !in_array('OfficeAssistant', $userRoles)) {
            throw new \Exception('Access denied. Only PGAM and Office Assistant can update student records.');
        }
        
        // Validate main supervisor if provided
        if (isset($data['main_supervisor_id'])) {
            Lecturer::findOrFail($data['main_supervisor_id']);
        }

        $student->update($data);
        return $student->fresh();
    }

    /**
     * Delete a student record with role-based access control.
     *
     * @param int $id
     * @return bool
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     * @throws \Exception
     */
    public function deleteStudent(int $id): bool
    {
        $student = Student::findOrFail($id);
        
        // Apply role-based access control
        $user = auth()->user();
        $userRoles = $user->roles->pluck('role_name')->toArray();

        // Only PGAM and Office Assistant can delete students
        if (!in_array('PGAM', $userRoles) && !in_array('OfficeAssistant', $userRoles)) {
            throw new \Exception('Access denied. Only PGAM and Office Assistant can delete student records.');
        }
        
        return $student->delete();
    }
}