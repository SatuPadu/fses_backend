<?php

namespace App\Modules\Student\Services;

use App\Modules\Student\Models\Student;
use App\Modules\UserManagement\Models\Lecturer;
use App\Modules\Evaluation\Models\CoSupervisor;

class StudentService
{
    /**
     * Calculate the current user's roles for a specific student.
     *
     * @param Student $student
     * @param array $userRoles
     * @return array
     */
    private function calculateUserRolesForStudent(Student $student, array $userRoles): array
    {
        $user = auth()->user();
        $roles = [];

        // Check if user is a Program Coordinator for this student's department
        if (in_array('ProgramCoordinator', $userRoles) && $student->department === $user->department) {
            $roles[] = 'ProgramCoordinator';
        }

        // Check if user is a Research Supervisor for this student
        if (in_array('ResearchSupervisor', $userRoles) && $student->mainSupervisor && $student->mainSupervisor->staff_number === $user->staff_number) {
            $roles[] = 'ResearchSupervisor';
        }

        // Check if user is a Co-Supervisor for this student
        if (in_array('CoSupervisor', $userRoles)) {
            $isCoSupervisor = $student->coSupervisors->some(function ($coSupervisor) use ($user) {
                return $coSupervisor->lecturer_id === $user->lecturer->id;
            });
            if ($isCoSupervisor) {
                $roles[] = 'CoSupervisor';
            }
        }

        // Check if user is a Chairperson for this student
        if (in_array('Chairperson', $userRoles)) {
            $isChairperson = $student->evaluations->some(function ($evaluation) use ($user) {
                return $evaluation->chairperson && $evaluation->chairperson->staff_number === $user->staff_number;
            });
            if ($isChairperson) {
                $roles[] = 'Chairperson';
            }
        }

        // Always check if user is an Examiner for this student and determine specific position
        foreach ($student->evaluations as $evaluation) {
            // Check if user is Examiner 1
            if ($evaluation->examiner1 && $evaluation->examiner1->staff_number === $user->staff_number) {
                $roles[] = 'Examiner 1';
            }
            // Check if user is Examiner 2
            if ($evaluation->examiner2 && $evaluation->examiner2->staff_number === $user->staff_number) {
                $roles[] = 'Examiner 2';
            }
            // Check if user is Examiner 3
            if ($evaluation->examiner3 && $evaluation->examiner3->staff_number === $user->staff_number) {
                $roles[] = 'Examiner 3';
            }
        }

        return $roles;
    }

    /**
     * Retrieve paginated and optionally filtered list of students.
     *
     * Role-based filtering behavior:
     * - PGAM: Can view all students
     * - Office Assistant: Can view all students
     * - PC (Program Coordinator): Can view students from their department
     * - RS (Research Supervisor): Can view students they supervise
     * - Chairperson: Can view students they're chairing
     * - Users with multiple roles: Can view students accessible through any of their roles
     *
     * @param int $numPerPage
     * @param array $filters
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getAllStudents(int $numPerPage, array $filters)
    {
        $query = Student::with(['program', 'mainSupervisor', 'evaluations', 'coSupervisors.lecturer']);

        // Apply basic filters first
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

        if (isset($filters['with_evaluation']) && filter_var($filters['with_evaluation'], FILTER_VALIDATE_BOOLEAN)) {
            $query->with(['evaluations']);
        }

        if (isset($filters['email'])) {
            $query->where('email', $filters['email']);
        }

        // Apply role-based filtering
        $user = auth()->user();
        $userRoles = $user->roles->pluck('role_name')->toArray();

        // Check if my_role filter is provided
        if (isset($filters['my_role']) && !empty($filters['my_role'])) {
            $roleToFilter = $filters['my_role'];
            
            // Check if user has PGAM or Office Assistant role (can see all data)
            if (in_array('PGAM', $userRoles) || in_array('OfficeAssistant', $userRoles)) {
                
                $query->where(function ($q) use ($user, $roleToFilter) {
                    switch ($roleToFilter) {
                        case 'ProgramCoordinator':
                            $q->where('department', $user->department);
                            break;
                            
                        case 'ResearchSupervisor':
                            $q->whereHas('mainSupervisor', function ($sq) use ($user) {
                                $sq->where('staff_number', $user->staff_number);
                            });
                            break;
                            
                        case 'CoSupervisor':
                            // Make sure user has lecturer relationship
                            if ($user->lecturer) {
                                $q->whereHas('coSupervisors', function ($csq) use ($user) {
                                    $csq->where('lecturer_id', $user->lecturer->id);
                                });
                            } else {
                                // If user doesn't have lecturer relationship, return no results
                                $q->whereRaw('1 = 0');
                            }
                            break;
                            
                        case 'Chairperson':
                            $q->whereHas('evaluations', function ($eq) use ($user) {
                                $eq->whereHas('chairperson', function ($cq) use ($user) {
                                    $cq->where('staff_number', $user->staff_number);
                                });
                            });
                            break;
                            
                        case 'Examiner 1':
                            $q->whereHas('evaluations', function ($eq) use ($user) {
                                $eq->whereHas('examiner1', function ($e1q) use ($user) {
                                    $e1q->where('staff_number', $user->staff_number);
                                });
                            });
                            break;
                            
                        case 'Examiner 2':
                            $q->whereHas('evaluations', function ($eq) use ($user) {
                                $eq->whereHas('examiner2', function ($e2q) use ($user) {
                                    $e2q->where('staff_number', $user->staff_number);
                                });
                            });
                            break;
                            
                        case 'Examiner 3':
                            $q->whereHas('evaluations', function ($eq) use ($user) {
                                $eq->whereHas('examiner3', function ($e3q) use ($user) {
                                    $e3q->where('staff_number', $user->staff_number);
                                });
                            });
                            break;
                            
                        default:
                            // Unknown role, return no results
                            $q->whereRaw('1 = 0');
                            break;
                    }
                });
            } else {
                
                $hasSpecificRole = false;
                switch ($roleToFilter) {
                    case 'ProgramCoordinator':
                        $hasSpecificRole = in_array('ProgramCoordinator', $userRoles);
                        if ($hasSpecificRole) {
                            $query->where('department', $user->department);
                        }
                        break;
                        
                    case 'ResearchSupervisor':
                        $hasSpecificRole = in_array('ResearchSupervisor', $userRoles);
                        if ($hasSpecificRole) {
                            $query->whereHas('mainSupervisor', function ($sq) use ($user) {
                                $sq->where('staff_number', $user->staff_number);
                            });
                        }
                        break;
                        
                    case 'CoSupervisor':
                        $hasSpecificRole = in_array('CoSupervisor', $userRoles);
                        if ($hasSpecificRole) {
                            if ($user->lecturer) {
                                $query->whereHas('coSupervisors', function ($csq) use ($user) {
                                    $csq->where('lecturer_id', $user->lecturer->id);
                                });
                            } else {
                                // If user doesn't have lecturer relationship, return no results
                                $query->whereRaw('1 = 0');
                            }
                        }
                        break;
                        
                    case 'Chairperson':
                        $hasSpecificRole = in_array('Chairperson', $userRoles);
                        if ($hasSpecificRole) {
                            $query->whereHas('evaluations', function ($eq) use ($user) {
                                $eq->whereHas('chairperson', function ($cq) use ($user) {
                                    $cq->where('staff_number', $user->staff_number);
                                });
                            });
                        }
                        break;
                        
                    case 'Examiner 1':
                    case 'Examiner 2':
                    case 'Examiner 3':
                        // Check if user has any examiner role - be more flexible here
                        $hasSpecificRole = in_array('Examiner', $userRoles) || 
                                         in_array('Examiner1', $userRoles) || 
                                         in_array('Examiner2', $userRoles) || 
                                         in_array('Examiner3', $userRoles);
                        if ($hasSpecificRole) {
                            $query->whereHas('evaluations', function ($eq) use ($user, $roleToFilter) {
                                switch ($roleToFilter) {
                                    case 'Examiner 1':
                                        $eq->whereHas('examiner1', function ($e1q) use ($user) {
                                            $e1q->where('staff_number', $user->staff_number);
                                        });
                                        break;
                                    case 'Examiner 2':
                                        $eq->whereHas('examiner2', function ($e2q) use ($user) {
                                            $e2q->where('staff_number', $user->staff_number);
                                        });
                                        break;
                                    case 'Examiner 3':
                                        $eq->whereHas('examiner3', function ($e3q) use ($user) {
                                            $e3q->where('staff_number', $user->staff_number);
                                        });
                                        break;
                                }
                            });
                        }
                        break;
                }
                
                // If user doesn't have the specific role they're filtering by, return no results
                if (!$hasSpecificRole) {
                    $query->whereRaw('1 = 0');
                }
            }
        } else {
            // No my_role filter provided - apply general role-based access control
            // [Your existing general role-based logic here]
            if (in_array('PGAM', $userRoles) || in_array('OfficeAssistant', $userRoles)) {
                // PGAM and Office Assistant can see all data - no additional filtering needed
            } else {
                // Apply your existing general role-based filtering logic
                $query->where(function ($q) use ($user, $userRoles) {
                    $hasAccess = false;

                    if (in_array('ProgramCoordinator', $userRoles)) {
                        $q->orWhere('department', $user->department);
                        $hasAccess = true;
                    }

                    if (in_array('ResearchSupervisor', $userRoles)) {
                        $q->orWhereHas('mainSupervisor', function ($sq) use ($user) {
                            $sq->where('staff_number', $user->staff_number);
                        });
                        $hasAccess = true;
                    }

                    if (in_array('CoSupervisor', $userRoles)) {
                        if ($user->lecturer) {
                            $q->orWhereHas('coSupervisors', function ($csq) use ($user) {
                                $csq->where('lecturer_id', $user->lecturer->id);
                            });
                        }
                        $hasAccess = true;
                    }

                    if (in_array('Chairperson', $userRoles)) {
                        $q->orWhereHas('evaluations', function ($eq) use ($user) {
                            $eq->whereHas('chairperson', function ($cq) use ($user) {
                                $cq->where('staff_number', $user->staff_number);
                            });
                        });
                        $hasAccess = true;
                    }

                    // Check if user is an Examiner
                    $q->orWhereHas('evaluations', function ($eq) use ($user) {
                        $eq->where(function ($exq) use ($user) {
                            $exq->whereHas('examiner1', function ($e1q) use ($user) {
                                $e1q->where('staff_number', $user->staff_number);
                            })
                            ->orWhereHas('examiner2', function ($e2q) use ($user) {
                                $e2q->where('staff_number', $user->staff_number);
                            })
                            ->orWhereHas('examiner3', function ($e3q) use ($user) {
                                $e3q->where('staff_number', $user->staff_number);
                            });
                        });
                    });
                    $hasAccess = true;

                    if (!$hasAccess) {
                        $q->whereRaw('1 = 0');
                    }
                });
            }
        }

        $paginator = $query->orderBy('created_at', 'desc')->paginate($numPerPage);

        // Add user roles for each student
        $paginator->getCollection()->transform(function ($student) use ($userRoles) {
            $student->user_roles = $this->calculateUserRolesForStudent($student, $userRoles);
            return $student;
        });

        return $paginator;
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

        // Extract co-supervisors data before creating student
        $coSupervisorIds = $data['co_supervisors'] ?? [];
        unset($data['co_supervisors']);

        // Create the student
        $student = Student::create($data);

        // Create co-supervisor relationships
        if (!empty($coSupervisorIds)) {
            foreach ($coSupervisorIds as $lecturerId) {
                $lecturer = Lecturer::findOrFail($lecturerId);
                
                CoSupervisor::create([
                    'student_id' => $student->id,
                    'lecturer_id' => $lecturer->is_from_fai ? $lecturer->id : null,
                    'external_name' => $lecturer->is_from_fai ? null : $lecturer->name,
                    'external_institution' => $lecturer->is_from_fai ? null : $lecturer->external_institution,
                ]);
            }
        }

        return $student->load('coSupervisors.lecturer');
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
        $student = Student::with(['program', 'mainSupervisor', 'evaluations', 'coSupervisors.lecturer'])->findOrFail($id);
        
        // Apply role-based access control
        $user = auth()->user();
        $userRoles = $user->roles->pluck('role_name')->toArray();

        // Check if user has PGAM or Office Assistant role (can see all data)
        if (in_array('PGAM', $userRoles) || in_array('OfficeAssistant', $userRoles)) {
            $student->user_roles = $this->calculateUserRolesForStudent($student, $userRoles);
            return $student;
        }

        // Check if user has access through any of their other roles
        $hasAccess = false;

        // Check if user is a Program Coordinator (can see their department)
        if (in_array('ProgramCoordinator', $userRoles)) {
            if ($student->department === $user->department) {
                $hasAccess = true;
            }
        }

        // Check if user is a Research Supervisor (can see students they supervise)
        if (in_array('ResearchSupervisor', $userRoles)) {
            if ($student->mainSupervisor && $student->mainSupervisor->staff_number === $user->staff_number) {
                $hasAccess = true;
            }
        }

        // Check if user is a Co-Supervisor (can see students they co-supervise)
        if (in_array('CoSupervisor', $userRoles)) {
            $isCoSupervisor = $student->coSupervisors->some(function ($coSupervisor) use ($user) {
                return $coSupervisor->lecturer_id === $user->lecturer->id;
            });
            if ($isCoSupervisor) {
                $hasAccess = true;
            }
        }

        // Check if user is a Chairperson (can see students they're chairing)
        if (in_array('Chairperson', $userRoles)) {
            $isChairperson = $student->evaluations->some(function ($evaluation) use ($user) {
                return $evaluation->chairperson && $evaluation->chairperson->staff_number === $user->staff_number;
            });
            if ($isChairperson) {
                $hasAccess = true;
            }
        }

        // Check if user is an Examiner (can see students they're examining)
        $isExaminer = $student->evaluations->some(function ($evaluation) use ($user) {
            return ($evaluation->examiner1 && $evaluation->examiner1->staff_number === $user->staff_number) ||
                   ($evaluation->examiner2 && $evaluation->examiner2->staff_number === $user->staff_number) ||
                   ($evaluation->examiner3 && $evaluation->examiner3->staff_number === $user->staff_number);
        });
        if ($isExaminer) {
            $hasAccess = true;
        }

        if ($hasAccess) {
            $student->user_roles = $this->calculateUserRolesForStudent($student, $userRoles);
            return $student;
        }

        throw new \Exception('Access denied. You do not have permission to view this student.');
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

        // Extract co-supervisors data before updating student
        $coSupervisorIds = $data['co_supervisors'] ?? null;
        unset($data['co_supervisors']);

        $student->update($data);

        // Update co-supervisor relationships if provided
        if ($coSupervisorIds !== null) {
            // Delete existing co-supervisor relationships
            $student->coSupervisors()->delete();

            // Create new co-supervisor relationships
            if (!empty($coSupervisorIds)) {
                foreach ($coSupervisorIds as $lecturerId) {
                    $lecturer = Lecturer::findOrFail($lecturerId);
                    
                    CoSupervisor::create([
                        'student_id' => $student->id,
                        'lecturer_id' => $lecturer->is_from_fai ? $lecturer->id : null,
                        'external_name' => $lecturer->is_from_fai ? null : $lecturer->name,
                        'external_institution' => $lecturer->is_from_fai ? null : $lecturer->external_institution,
                    ]);
                }
            }
        }

        return $student->fresh(['coSupervisors.lecturer']);
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