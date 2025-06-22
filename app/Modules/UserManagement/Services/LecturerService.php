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

        // Check if user has PGAM role (can see all data)
        if (in_array('PGAM', $userRoles)) {
            // PGAM can see all data - no additional filtering needed
        }
        // Check if user has Office Assistant role (can see all data)
        elseif (in_array('OfficeAssistant', $userRoles)) {
            // Office Assistant can see all data - no additional filtering needed
        }
        // Check if user is a Program Coordinator (can only see lecturers from their department)
        elseif (in_array('ProgramCoordinator', $userRoles)) {
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
}
