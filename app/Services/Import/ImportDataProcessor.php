<?php

namespace App\Services\Import;

use App\Modules\Program\Models\Program;
use App\Modules\UserManagement\Models\Lecturer;
use App\Modules\Auth\Models\User;
use App\Modules\Student\Models\Student;
use App\Modules\Evaluation\Models\Evaluation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class ImportDataProcessor
{
    protected $progressTracker;

    public function __construct(ImportProgressTracker $progressTracker)
    {
        $this->progressTracker = $progressTracker;
    }

    public function processRow($row, $rowNumber)
    {
        $result = [
            'programs_created' => 0,
            'programs_updated' => 0,
            'lecturers_created' => 0,
            'lecturers_updated' => 0,
            'users_created' => 0,
            'users_updated' => 0,
            'students_created' => 0,
            'students_updated' => 0,
            'evaluations_created' => 0,
            'evaluations_updated' => 0,
            'co_supervisors_created' => 0,
            'co_supervisors_updated' => 0
        ];

        // 1. Create/Update Program
        $this->progressTracker->updateStepProgress("Processing program data for row {$rowNumber}...");
        $program = $this->createOrUpdateProgram($row, $result);
        $this->logProgramImport($program, $row, $result);

        // 2. Create/Update Lecturers and Users
        $this->progressTracker->updateStepProgress("Processing main supervisor data for row {$rowNumber}...");
        $mainSupervisor = $this->createOrUpdateLecturer($row, 'main_supervisor', $rowNumber, $result);
        $this->logLecturerImport($mainSupervisor, 'main_supervisor', $row, $result);

        $this->progressTracker->updateStepProgress("Processing co-supervisor data for row {$rowNumber}...");
        $coSupervisor = $this->createOrUpdateLecturer($row, 'co_supervisor', $rowNumber, $result);
        $this->logLecturerImport($coSupervisor, 'co_supervisor', $row, $result);

        $this->progressTracker->updateStepProgress("Processing examiner 1 data for row {$rowNumber}...");
        $examiner1 = $this->createOrUpdateLecturer($row, 'examiner1', $rowNumber, $result);
        $this->logLecturerImport($examiner1, 'examiner1', $row, $result);

        $this->progressTracker->updateStepProgress("Processing examiner 2 data for row {$rowNumber}...");
        $examiner2 = $this->createOrUpdateLecturer($row, 'examiner2', $rowNumber, $result);
        $this->logLecturerImport($examiner2, 'examiner2', $row, $result);

        $this->progressTracker->updateStepProgress("Processing examiner 3 data for row {$rowNumber}...");
        $examiner3 = $this->createOrUpdateLecturer($row, 'examiner3', $rowNumber, $result);
        $this->logLecturerImport($examiner3, 'examiner3', $row, $result);

        $this->progressTracker->updateStepProgress("Processing chairperson data for row {$rowNumber}...");
        $chairperson = $this->createOrUpdateLecturer($row, 'chairperson', $rowNumber, $result);
        $this->logLecturerImport($chairperson, 'chairperson', $row, $result);

        // 3. Create/Update Student
        $this->progressTracker->updateStepProgress("Processing student data for row {$rowNumber}...");
        $student = $this->createOrUpdateStudent($row, $program, $mainSupervisor, $rowNumber, $result);
        $this->logStudentImport($student, $row, $result);

        // 4. Create/Update Co-Supervisor relationship
        $this->progressTracker->updateStepProgress("Processing co-supervisor relationship for row {$rowNumber}...");
        $this->createOrUpdateCoSupervisor($student, $coSupervisor, $rowNumber, $result);

        // 5. Create/Update Evaluation
        $this->progressTracker->updateStepProgress("Processing evaluation data for row {$rowNumber}...");
        $evaluation = $this->createOrUpdateEvaluation($row, $student, $examiner1, $examiner2, $examiner3, $chairperson, $rowNumber, $result);
        $this->logEvaluationImport($evaluation, $row, $result);

        return $result;
    }

    protected function createOrUpdateProgram($row, &$result)
    {
        $programData = [
            'program_name' => $row['program_name'],
            'program_code' => $this->generateProgramCode($row['program_name']),
            'department' => $row['student_department'],
            'total_semesters' => $this->getTotalSemesters($row['program_name']),
            'evaluation_semester' => $this->getEvaluationSemester($row['program_name'])
        ];

        $existingProgram = Program::where('program_name', $row['program_name'])->first();
        
        if ($existingProgram) {
            $existingProgram->update($programData);
            $result['programs_updated']++;
            return $existingProgram;
        } else {
            $program = Program::create($programData);
            $result['programs_created']++;
            return $program;
        }
    }

    protected function createOrUpdateLecturer($row, $type, $rowNumber, &$result)
    {
        $prefix = $type . '_';
        $staffNumber = $row[$prefix . 'staff_number'];
        $email = $row[$prefix . 'email'];
        $isCoordinator = (bool)($row[$prefix . 'is_coordinator'] ?? false);
        $externalInstitution = $row[$prefix . 'external_institution'] ?? null;

        // Check if lecturer exists by email
        $lecturer = Lecturer::where('email', $email)->first();

        $lecturerData = [
            'name' => $row[$prefix . 'name'],
            'title' => $row[$prefix . 'title'],
            'department' => $row[$prefix . 'department'],
            'email' => $email,
            'phone' => $row[$prefix . 'phone'] ?? null,
            'specialization' => $row[$prefix . 'specialization'] ?? null,
            'external_institution' => $externalInstitution,
            'is_from_fai' => $this->determineIfFromFAI($type, $externalInstitution),
            'staff_number' => $staffNumber
        ];

        if ($lecturer) {
            // Update existing lecturer
            $lecturer->update($lecturerData);
            $result['lecturers_updated']++;
        } else {
            // Create new lecturer
            $lecturer = Lecturer::create($lecturerData);
            $result['lecturers_created']++;
        }

        // Create user account for internal FAI lecturers only
        if ($lecturer->is_from_fai) {
            $user = $this->createOrUpdateUser($lecturer, $type, $isCoordinator, $result);
        }

        return $lecturer;
    }

    protected function determineIfFromFAI($lecturerType, $externalInstitution)
    {
        // Co-supervisors and examiner2 can be external
        if ($lecturerType === 'co_supervisor' || $lecturerType === 'examiner2') {
            return empty($externalInstitution);
        }
        
        // All other lecturer types are internal FAI lecturers
        return true;
    }

    protected function createOrUpdateUser($lecturer, $lecturerType, $isCoordinator, &$result)
    {
        $user = User::where('email', $lecturer->email)->first();

        $userData = [
            'staff_number' => $lecturer->staff_number,
            'name' => $lecturer->name,
            'email' => $lecturer->email,
            'password' => Hash::make($lecturer->staff_number), 
            'department' => $lecturer->department,
            'is_password_updated' => false,
            'is_active' => true
        ];

        if (!$user) {
            $user = User::create($userData);
            $result['users_created']++;
        } else {
            $user->update($userData);
            $result['users_updated']++;
        }

        // Assign multiple roles based on lecturer type and coordinator status
        $this->assignUserRoles($user, $lecturer, $lecturerType, $isCoordinator);

        return $user;
    }

    protected function assignUserRoles($user, $lecturer, $lecturerType, $isCoordinator)
    {
        $rolesToAssign = [];

        // Get the primary role based on lecturer type
        $primaryRole = $this->determineRoleForLecturer($lecturerType);
        if ($primaryRole) {
            $rolesToAssign[] = $primaryRole;
        }

        // Add Program Coordinator role if is_coordinator is true
        if ($isCoordinator) {
            $rolesToAssign[] = \App\Enums\UserRole::PROGRAM_COORDINATOR;
        }

        // Assign all roles
        foreach ($rolesToAssign as $roleName) {
            $this->assignSingleRole($user, $lecturer, $lecturerType, $roleName, $isCoordinator);
        }
    }

    protected function assignSingleRole($user, $lecturer, $lecturerType, $roleName, $isCoordinator)
    {
        // Find the role
        $role = \App\Modules\UserManagement\Models\Role::where('role_name', $roleName)->first();
        
        if ($role) {
            // Check if user already has this role
            $existingRole = DB::table('user_roles')
                ->where('user_id', $user->id)
                ->where('role_id', $role->id)
                ->first();
            
            if (!$existingRole) {
                // Assign the role
                DB::table('user_roles')->insert([
                    'user_id' => $user->id,
                    'role_id' => $role->id,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
                
                $this->progressTracker->updateDetailedProgress("Role assigned", [
                    'table' => 'user_roles',
                    'action' => 'created',
                    'user_id' => $user->id,
                    'role_id' => $role->id,
                    'role_name' => $roleName,
                    'lecturer_type' => $lecturerType,
                    'is_coordinator' => $isCoordinator,
                    'lecturer_name' => $lecturer->name,
                    'lecturer_email' => $lecturer->email
                ]);
            }
        }
    }

    protected function determineRoleForLecturer($lecturerType)
    {
        // Assign roles based on lecturer type
        switch ($lecturerType) {
            case 'main_supervisor':
                return \App\Enums\UserRole::SUPERVISOR;
            
            case 'co_supervisor':
                return \App\Enums\UserRole::CO_SUPERVISOR;
            
            case 'chairperson':
                return \App\Enums\UserRole::CHAIRPERSON;
            
            case 'examiner1':
            case 'examiner2':
            case 'examiner3':
                // Examiners could be assigned different roles based on your business logic
                // For now, assigning them as supervisors
                return \App\Enums\UserRole::SUPERVISOR;
            
            default:
                return \App\Enums\UserRole::SUPERVISOR;
        }
    }

    protected function createOrUpdateStudent($row, $program, $mainSupervisor, $rowNumber, &$result)
    {
        $studentData = [
            'matric_number' => $row['student_matric_number'],
            'name' => $row['student_name'],
            'email' => $row['student_email'],
            'program_id' => $program->id,
            'current_semester' => $row['current_semester'],
            'department' => $row['student_department'],
            'country' => $row['country'] ?? null,
            'main_supervisor_id' => $mainSupervisor->id,
            'evaluation_type' => $row['evaluation_type'],
            'research_title' => $row['research_title'] ?? null,
        ];

        $existingStudent = Student::where('matric_number', $row['student_matric_number'])->first();

        if ($existingStudent) {
            $existingStudent->update($studentData);
            $result['students_updated']++;
            return $existingStudent;
        } else {
            $student = Student::create($studentData);
            $result['students_created']++;
            return $student;
        }
    }

    protected function createOrUpdateCoSupervisor($student, $coSupervisor, $rowNumber, &$result)
    {
        if (!$coSupervisor) return;

        // Use the co_supervisors table
        $existingCoSupervisor = DB::table('co_supervisors')
            ->where('student_id', $student->id)
            ->where('lecturer_id', $coSupervisor->id)
            ->first();

        if ($existingCoSupervisor) {
            DB::table('co_supervisors')
                ->where('id', $existingCoSupervisor->id)
                ->update([
                    'external_name' => $coSupervisor->is_from_fai ? null : $coSupervisor->name,
                    'external_institution' => $coSupervisor->external_institution,
                    'updated_at' => now()
                ]);
            $result['co_supervisors_updated']++;
        } else {
            DB::table('co_supervisors')->insert([
                'student_id' => $student->id,
                'lecturer_id' => $coSupervisor->is_from_fai ? $coSupervisor->id : null,
                'external_name' => $coSupervisor->is_from_fai ? null : $coSupervisor->name,
                'external_institution' => $coSupervisor->external_institution,
                'created_at' => now(),
                'updated_at' => now()
            ]);
            $result['co_supervisors_created']++;
        }

        // Log co-supervisor relationship
        $this->progressTracker->updateDetailedProgress("Co-supervisor relationship created", [
            'table' => 'co_supervisors',
            'action' => $existingCoSupervisor ? 'updated' : 'created',
            'student_id' => $student->id,
            'lecturer_id' => $coSupervisor->is_from_fai ? $coSupervisor->id : null,
            'external_name' => $coSupervisor->is_from_fai ? null : $coSupervisor->name,
            'external_institution' => $coSupervisor->external_institution,
            'is_external' => !$coSupervisor->is_from_fai,
            'co_supervisor_name' => $coSupervisor->name,
            'co_supervisor_email' => $coSupervisor->email
        ]);
    }

    protected function createOrUpdateEvaluation($row, $student, $examiner1, $examiner2, $examiner3, $chairperson, $rowNumber, &$result)
    {
        $evaluationData = [
            'student_id' => $student->id,
            'nomination_status' => $row['nomination_status'],
            'examiner1_id' => $examiner1->id,
            'examiner2_id' => $examiner2->id,
            'examiner3_id' => $examiner3->id,
            'chairperson_id' => $chairperson->id,
            'semester' => $row['semester'],
            'academic_year' => $row['academic_year'],
            'is_auto_assigned' => false,
            'is_postponed' => (bool)($row['is_postponed'] ?? false),
            'postponement_reason' => $row['postponement_reason'] ?? null
        ];

        $existingEvaluation = Evaluation::where('student_id', $student->id)->first();

        if ($existingEvaluation) {
            $existingEvaluation->update($evaluationData);
            $result['evaluations_updated']++;
            return $existingEvaluation;
        } else {
            $evaluation = Evaluation::create($evaluationData);
            $result['evaluations_created']++;
            return $evaluation;
        }
    }

    // Logging methods for detailed progress
    protected function logProgramImport($program, $row, $result)
    {
        $action = $result['programs_created'] > 0 ? 'created' : 'updated';
        $this->progressTracker->updateDetailedProgress("Program data {$action}", [
            'table' => 'programs',
            'action' => $action,
            'data' => $program->toArray(),
            'row_data' => [
                'program_name' => $row['program_name'],
                'department' => $row['student_department']
            ]
        ]);
    }

    protected function logLecturerImport($lecturer, $type, $row, $result)
    {
        $action = $result['lecturers_created'] > 0 ? 'created' : 'updated';
        $prefix = $type . '_';
        $isCoordinator = (bool)($row[$prefix . 'is_coordinator'] ?? false);
        
        // Get user data if internal lecturer
        $userData = null;
        $userRoles = [];
        if ($lecturer->is_from_fai) {
            $user = User::where('lecturer_id', $lecturer->id)->first();
            if ($user) {
                $userData = $user->toArray();
                // Get user roles for logging
                $roles = DB::table('user_roles')
                    ->join('roles', 'user_roles.role_id', '=', 'roles.id')
                    ->where('user_roles.user_id', $user->id)
                    ->pluck('roles.role_name')
                    ->toArray();
                $userRoles = $roles;
            }
        }

        $this->progressTracker->updateDetailedProgress("{$type} data {$action}", [
            'table' => 'lecturers',
            'action' => $action,
            'lecturer_data' => $lecturer->toArray(),
            'user_data' => $userData,
            'user_roles' => $userRoles,
            'is_external' => !$lecturer->is_from_fai,
            'is_coordinator' => $isCoordinator,
            'row_data' => [
                'name' => $row[$prefix . 'name'],
                'email' => $row[$prefix . 'email'],
                'title' => $row[$prefix . 'title'],
                'department' => $row[$prefix . 'department'],
                'is_coordinator' => $isCoordinator
            ]
        ]);
    }

    protected function logStudentImport($student, $row, $result)
    {
        $action = $result['students_created'] > 0 ? 'created' : 'updated';
        $this->progressTracker->updateDetailedProgress("Student data {$action}", [
            'table' => 'students',
            'action' => $action,
            'data' => $student->toArray(),
            'row_data' => [
                'matric_number' => $row['student_matric_number'],
                'name' => $row['student_name'],
                'email' => $row['student_email'],
                'program_name' => $row['program_name'],
                'country' => $row['country'] ?? null
            ]
        ]);
    }

    protected function logEvaluationImport($evaluation, $row, $result)
    {
        $action = $result['evaluations_created'] > 0 ? 'created' : 'updated';
        $this->progressTracker->updateDetailedProgress("Evaluation data {$action}", [
            'table' => 'student_evaluations',
            'action' => $action,
            'data' => $evaluation->toArray(),
            'row_data' => [
                'student_matric_number' => $row['student_matric_number'],
                'nomination_status' => $row['nomination_status'],
                'semester' => $row['semester'],
                'academic_year' => $row['academic_year']
            ]
        ]);
    }

    protected function generateProgramCode($programName)
    {
        $mapping = [
            'Master of Computer Science' => 'MCS',
            'Master of Information Technology' => 'MIT',
            'Doctor of Philosophy' => 'PhD',
            'Master of Philosophy' => 'MPhil',
            'Doctor of Software Engineering' => 'DSE'
        ];

        return $mapping[$programName] ?? 'UNKNOWN';
    }

    protected function getTotalSemesters($programName)
    {
        $mapping = [
            'Master of Computer Science' => 4,
            'Master of Information Technology' => 4,
            'Doctor of Philosophy' => 8,
            'Master of Philosophy' => 4,
            'Doctor of Software Engineering' => 8
        ];

        return $mapping[$programName] ?? 4;
    }

    protected function getEvaluationSemester($programName)
    {
        $mapping = [
            'Master of Computer Science' => 3,
            'Master of Information Technology' => 3,
            'Doctor of Philosophy' => 3,
            'Master of Philosophy' => 2,
            'Doctor of Software Engineering' => 8
        ];

        return $mapping[$programName] ?? 3;
    }
} 