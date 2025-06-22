<?php

namespace App\Modules\UserManagement\Services;

use Illuminate\Support\Facades\DB;
use App\Enums\Department;
use App\Modules\Auth\Models\User;
use Exception;
use Illuminate\Support\Facades\Hash;
use App\Modules\UserManagement\Models\Lecturer;

class LecturerService 
{
    /**
     * Returns all non-deleted Lecturer models from the database.
     * 
     * @param int $numPerPage
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getLecturers(int $numPerPage, array $request)
    {
        // Start a new query builder instance
        $query = Lecturer::with('user');

        // Apply filters to query builder
        if (isset($request['name'])) {
            $query->where('name', 'like', '%' . $request['name'] . '%');
        }

        if (isset($request['title'])) {
            $query->where('title', '=', $request['title']);
        }

        if (isset($request['department'])) {
            $query->where('department', '=', $request['department']);
        }

        if (isset($request['is_from_fai'])) {
            $query->where('is_from_fai', '=', $request['is_from_fai']);
        }

        if (isset($request['staff_number'])) {
            $query->where('staff_number', 'like', '%' . $request['staff_number'] . '%');
        }

        if (isset($request['external_institution'])) {
            $query->where('external_institution', 'like', '%' . $request['external_institution'] . '%');
        }

        if (isset($request['specialization'])) {
            $query->where('specialization', 'like', '%' . $request['specialization'] . '%');
        }

        if (isset($request['email'])) {
            $query->where('email', 'like', '%' . $request['email'] . '%');
        }

        if (isset($request['phone'])) {
            $query->where('phone', 'like', '%' . $request['phone'] . '%');
        }

        // Apply role-based filtering
        $user = auth()->user();
        $userRoles = $user->roles->pluck('role_name')->toArray();


        // Check if user is a Program Coordinator (can only see lecturers from their department)
        if (in_array('ProgramCoordinator', $userRoles)) {
            $query->where('department', $user->department);
        }
        // Check if user is a Supervisor (can only see lecturers related to their students)
        elseif (in_array('Supervisor', $userRoles)) {
            $query->where(function ($q) use ($user) {
                // Can see themselves
                $q->where('staff_number', $user->staff_number)
                // Can see co-supervisors of their students
                ->orWhereHas('coSupervisors', function ($coSupQ) use ($user) {
                    $coSupQ->whereHas('student', function ($studQ) use ($user) {
                        $studQ->whereHas('mainSupervisor', function ($mainSupQ) use ($user) {
                            $mainSupQ->where('staff_number', $user->staff_number);
                        });
                    });
                })
                // Can see examiners of their students
                ->orWhereHas('examinerEvaluations', function ($examQ) use ($user) {
                    $examQ->whereHas('student', function ($studQ) use ($user) {
                        $studQ->whereHas('mainSupervisor', function ($mainSupQ) use ($user) {
                            $mainSupQ->where('staff_number', $user->staff_number);
                        });
                    });
                })
                // Can see chairpersons of their students
                ->orWhereHas('chairpersonEvaluations', function ($chairQ) use ($user) {
                    $chairQ->whereHas('student', function ($studQ) use ($user) {
                        $studQ->whereHas('mainSupervisor', function ($mainSupQ) use ($user) {
                            $mainSupQ->where('staff_number', $user->staff_number);
                        });
                    });
                });
            });
        }
        // Check if user is a Chairperson (can only see lecturers related to students they chair)
        elseif (in_array('Chairperson', $userRoles)) {
            $query->where(function ($q) use ($user) {
                // Can see themselves
                $q->where('staff_number', $user->staff_number)
                // Can see supervisors of students they chair
                ->orWhereHas('supervisedStudents', function ($studQ) use ($user) {
                    $studQ->whereHas('evaluations', function ($evalQ) use ($user) {
                        $evalQ->whereHas('chairperson', function ($chairQ) use ($user) {
                            $chairQ->where('staff_number', $user->staff_number);
                        });
                    });
                })
                // Can see examiners of students they chair
                ->orWhereHas('examinerEvaluations', function ($examQ) use ($user) {
                    $examQ->whereHas('student', function ($studQ) use ($user) {
                        $studQ->whereHas('evaluations', function ($evalQ) use ($user) {
                            $evalQ->whereHas('chairperson', function ($chairQ) use ($user) {
                                $chairQ->where('staff_number', $user->staff_number);
                            });
                        });
                    });
                });
            });
        }
        // Default: no access (empty result)
        else {
            $query->whereRaw('1 = 0'); // This will return no results
        }

        // Execute final query and returns results
        return $query->paginate($numPerPage);
    }

    /**
     * Adds new Lecturer model into the database.
     * 
     * @param array $request
     * @throws \Exception
     * @return array{lecturer_id: mixed}
     */
    public function newLecturer(array $request): Lecturer 
    {
        try{
            // Start database transaction
            DB::beginTransaction();

            // Create new lecturer entry
            $lecturer = Lecturer::create([
                'name' => $request['name'],
                'email' => $request['email'],
                'staff_number' => $request['staff_number'],
                'phone' => $request['phone'],
                'department' => $request['department'],
                'title' => $request['title'],
                'is_from_fai' => !($request['department'] == Department::OTHER),
                'external_institution' => $request['external_institution'],
                'specialization' => $request['specialization'],
            ]);

            // If lecturer is part of FAI, create user entry
            if($lecturer->is_from_fai) {
                $user = User::create([
                    'staff_number' => $lecturer->staff_number,
                    'name' => $request['name'],
                    'email' => $request['email'],
                    'password' => Hash::make( $lecturer->staff_number),
                    'department' => $request['department'],
                ]);
                $lecturer->user_id = $user->id;
                $lecturer->save();
            }

            // Commit changes to database and return lecturer instance
            DB::commit();
            return $lecturer;

        } catch(Exception $e) {
            // If exception occurs, reverse made changes
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Updates Lecturer model info in the database.
     * 
     * @param int $id
     * @param array $request
     * @throws \Exception
     * @return void
     */
    public function updateLecturer($id, array $request): Lecturer 
    {
        try {
            // Start database transaction
            DB::beginTransaction();

            // Find lecturer in database
            $lecturer = Lecturer::find($id);
            if (!$lecturer) {
                throw new Exception('Lecturer not found', 404);
            }
            
            // Update lecturer info
            $lecturer->name = $request['name'];
            $lecturer->title = $request['title'];
            $lecturer->department = $request['department'];
            $lecturer->external_institution = $request['external_institution'];
            $lecturer->is_from_fai = !($request['department'] == Department::OTHER);
            $lecturer->specialization = $request['specialization'];
            $lecturer->email = $request['email'];
            $lecturer->phone = $request['phone'];
            $lecturer->save();

            // If lecturer is part of FAI, update corresponding user info
            if(isset($lecturer->is_from_fai)) {
                $user = User::updateOrCreate(['id' => $lecturer->user_id], [
                    'staff_number' => $lecturer->staff_number,
                    'lecturer_id' => $lecturer->id,
                    'name' => $request['name'],
                    'email' => $request['email'],
                    'password' => Hash::make( $lecturer->staff_number),
                    'department' => $request['department'],
                ]);

                if(!isset($lecturer->user_id)) {
                    $lecturer->user_id = $user->id;
                    $lecturer->save();
                }
            }

            // Commit changes to database and return lecturer instance
            DB::commit();
            return $lecturer;
            
        } catch (Exception $e) {
            // If exception occurs, reverse made changes
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Soft-deletes Lecturer model
     * 
     * @param int $id
     * @return void
     */
    public function deleteLecturer($id): void 
    {
        DB::transaction(function () use ($id) {
            // Search and delete lecturer entry from database
            // If lecturer is part of FAI, delete corresponding user entry
            $lecturer = Lecturer::findOrFail($id);
            if($lecturer->is_from_fai) {
                User::find($lecturer->user_id)->delete();
            }
            $lecturer->delete();
        });
    }

    /**
     * Get lecturer details with workload statistics for all roles.
     *
     * @param int $lecturerId
     * @param string|null $semester
     * @param string|null $academicYear
     * @return array
     */
    public function getLecturerDetails(int $lecturerId, ?string $semester = null, ?string $academicYear = null): array
    {
        $lecturer = Lecturer::with(['user', 'user.roles'])->findOrFail($lecturerId);
        
        // Get current semester and academic year if not provided
        if (!$semester || !$academicYear) {
            $currentEvaluation = \App\Modules\Evaluation\Models\Evaluation::latest()->first();
            $semester = $semester ?? $currentEvaluation->semester ?? null;
            $academicYear = $academicYear ?? $currentEvaluation->academic_year ?? null;
        }

        $workloadStats = [
            'supervisor' => $this->getSupervisorWorkload($lecturerId, $semester, $academicYear),
            'examiner' => $this->getExaminerWorkload($lecturerId, $semester, $academicYear),
            'chairperson' => $this->getChairpersonWorkload($lecturerId, $semester, $academicYear),
            'co_supervisor' => $this->getCoSupervisorWorkload($lecturerId, $semester, $academicYear),
        ];

        return [
            'lecturer' => $lecturer,
            'workload_statistics' => $workloadStats,
            'semester' => $semester,
            'academic_year' => $academicYear,
        ];
    }

    /**
     * Get supervisor workload statistics.
     *
     * @param int $lecturerId
     * @param string|null $semester
     * @param string|null $academicYear
     * @return array
     */
    private function getSupervisorWorkload(int $lecturerId, ?string $semester, ?string $academicYear): array
    {
        $query = \App\Modules\Student\Models\Student::where('main_supervisor_id', $lecturerId)
            ->with(['evaluations', 'program']);

        if ($semester && $academicYear) {
            $query->whereHas('evaluations', function ($q) use ($semester, $academicYear) {
                $q->where('semester', $semester)
                  ->where('academic_year', $academicYear);
            });
        }

        $students = $query->get();

        $workloadByProgram = $students->groupBy('program.name')->map(function ($programStudents) {
            return [
                'total_students' => $programStudents->count(),
                'nominated' => $programStudents->filter(function ($student) {
                    return $student->evaluations->where('nomination_status', 'Nominated')->count() > 0;
                })->count(),
                'pending' => $programStudents->filter(function ($student) {
                    return $student->evaluations->where('nomination_status', 'Pending')->count() > 0;
                })->count(),
                'postponed' => $programStudents->filter(function ($student) {
                    return $student->evaluations->where('is_postponed', true)->count() > 0;
                })->count(),
                'locked' => $programStudents->filter(function ($student) {
                    return $student->evaluations->where('nomination_status', 'Locked')->count() > 0;
                })->count(),
            ];
        });

        return [
            'total_students' => $students->count(),
            'by_program' => $workloadByProgram,
        ];
    }

    /**
     * Get examiner workload statistics.
     *
     * @param int $lecturerId
     * @param string|null $semester
     * @param string|null $academicYear
     * @return array
     */
    private function getExaminerWorkload(int $lecturerId, ?string $semester, ?string $academicYear): array
    {
        $query = \App\Modules\Evaluation\Models\Evaluation::where(function ($q) use ($lecturerId) {
            $q->where('examiner1_id', $lecturerId)
              ->orWhere('examiner2_id', $lecturerId)
              ->orWhere('examiner3_id', $lecturerId);
        })->with(['student.program']);

        if ($semester && $academicYear) {
            $query->where('semester', $semester)
                  ->where('academic_year', $academicYear);
        }

        $evaluations = $query->get();

        $workloadByProgram = $evaluations->groupBy('student.program.name')->map(function ($programEvaluations) use ($lecturerId) {
            $examiner1Count = $programEvaluations->where('examiner1_id', $lecturerId)->count();
            $examiner2Count = $programEvaluations->where('examiner2_id', $lecturerId)->count();
            $examiner3Count = $programEvaluations->where('examiner3_id', $lecturerId)->count();

            return [
                'total_sessions' => $examiner1Count + $examiner2Count + $examiner3Count,
                'as_examiner1' => $examiner1Count,
                'as_examiner2' => $examiner2Count,
                'as_examiner3' => $examiner3Count,
            ];
        });

        $totalSessions = $evaluations->filter(function ($evaluation) use ($lecturerId) {
            return in_array($lecturerId, [
                $evaluation->examiner1_id,
                $evaluation->examiner2_id,
                $evaluation->examiner3_id
            ]);
        })->count();

        return [
            'total_sessions' => $totalSessions,
            'by_program' => $workloadByProgram,
        ];
    }

    /**
     * Get chairperson workload statistics.
     *
     * @param int $lecturerId
     * @param string|null $semester
     * @param string|null $academicYear
     * @return array
     */
    private function getChairpersonWorkload(int $lecturerId, ?string $semester, ?string $academicYear): array
    {
        $query = \App\Modules\Evaluation\Models\Evaluation::where('chairperson_id', $lecturerId)
            ->with(['student.program']);

        if ($semester && $academicYear) {
            $query->where('semester', $semester)
                  ->where('academic_year', $academicYear);
        }

        $evaluations = $query->get();

        $workloadByProgram = $evaluations->groupBy('student.program.name')->map(function ($programEvaluations) {
            return [
                'total_sessions' => $programEvaluations->count(),
            ];
        });

        return [
            'total_sessions' => $evaluations->count(),
            'by_program' => $workloadByProgram,
        ];
    }

    /**
     * Get co-supervisor workload statistics.
     *
     * @param int $lecturerId
     * @param string|null $semester
     * @param string|null $academicYear
     * @return array
     */
    private function getCoSupervisorWorkload(int $lecturerId, ?string $semester, ?string $academicYear): array
    {
        $query = \App\Modules\Evaluation\Models\CoSupervisor::where('lecturer_id', $lecturerId)
            ->with(['student.program', 'student.evaluations']);

        $coSupervisors = $query->get();

        // Filter by semester and academic year if provided
        if ($semester && $academicYear) {
            $coSupervisors = $coSupervisors->filter(function ($coSupervisor) use ($semester, $academicYear) {
                return $coSupervisor->student->evaluations->where('semester', $semester)
                    ->where('academic_year', $academicYear)->count() > 0;
            });
        }

        $workloadByProgram = $coSupervisors->groupBy('student.program.name')->map(function ($programCoSupervisors) {
            return [
                'total_students' => $programCoSupervisors->count(),
            ];
        });

        return [
            'total_students' => $coSupervisors->count(),
            'by_program' => $workloadByProgram,
        ];
    }
}
