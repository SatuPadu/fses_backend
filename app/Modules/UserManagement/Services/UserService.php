<?php

namespace App\Modules\UserManagement\Services;

use Illuminate\Support\Facades\DB;
use App\Enums\Department;
use App\Modules\Auth\Models\User;
use Exception;
use Illuminate\Support\Facades\Hash;
use App\Modules\UserManagement\Models\Lecturer;

class UserService 
{
    /**
     * Returns all non-deleted User models from the database.
     * 
     * @param int $numPerPage
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getUsers(int $numPerPage, array $request)
    {
        // Start a new query builder instance
        $query = User::with(['lecturer', 'roles']);

        // Apply filters to query builder
        if (isset($request['name'])) {
            $query->where('name', 'like', '%' . $request['name'] . '%');
        }

        if (isset($request['department'])) {
            $query->where('department', '=', $request['department']);
        }

        if (isset($request['staff_number'])) {
            $query->where('staff_number', 'like', '%' . $request['staff_number'] . '%');
        }

        if (isset($request['email'])) {
            $query->where('email', 'like', '%' . $request['email'] . '%');
        }
        if (isset($request['is_active'])) {
            $query->where('is_active', $request['is_active']);
        }

        if (isset($request['role'])) {
            $query->byRole($request['role']);
        }

        // Apply role-based filtering
        $user = auth()->user();
        $userRoles = $user->roles->pluck('role_name')->toArray();


        if (in_array('PGAM', $userRoles)) {
        }
        elseif (in_array('OfficeAssistant', $userRoles)) {
        }
        // Check if user is a Program Coordinator (can only see users from their department) 
        elseif (in_array('ProgramCoordinator', $userRoles)) {
            $query->where('department', $user->department);
        }
        // Check if user is a Supervisor (can only see users they supervise or are related to their students)
        elseif (in_array('Supervisor', $userRoles)) {
            $query->where(function ($q) use ($user) {
                // Can see themselves
                $q->where('staff_number', $user->staff_number)
                // Can see co-supervisors of their students
                ->orWhereHas('lecturer', function ($lectQ) use ($user) {
                    $lectQ->whereHas('coSupervisors', function ($coSupQ) use ($user) {
                        $coSupQ->whereHas('student', function ($studQ) use ($user) {
                            $studQ->whereHas('mainSupervisor', function ($mainSupQ) use ($user) {
                                $mainSupQ->where('staff_number', $user->staff_number);
                            });
                        });
                    });
                })
                // Can see examiners of their students
                ->orWhereHas('lecturer', function ($lectQ) use ($user) {
                    $lectQ->whereHas('examinerEvaluations', function ($examQ) use ($user) {
                        $examQ->whereHas('student', function ($studQ) use ($user) {
                            $studQ->whereHas('mainSupervisor', function ($mainSupQ) use ($user) {
                                $mainSupQ->where('staff_number', $user->staff_number);
                            });
                        });
                    });
                })
                // Can see chairpersons of their students
                ->orWhereHas('lecturer', function ($lectQ) use ($user) {
                    $lectQ->whereHas('chairpersonEvaluations', function ($chairQ) use ($user) {
                        $chairQ->whereHas('student', function ($studQ) use ($user) {
                            $studQ->whereHas('mainSupervisor', function ($mainSupQ) use ($user) {
                                $mainSupQ->where('staff_number', $user->staff_number);
                            });
                        });
                    });
                });
            });
        }
        // Check if user is a Chairperson (can only see users related to students they chair)
        elseif (in_array('Chairperson', $userRoles)) {
            $query->where(function ($q) use ($user) {
                // Can see themselves
                $q->where('staff_number', $user->staff_number)
                // Can see supervisors of students they chair
                ->orWhereHas('lecturer', function ($lectQ) use ($user) {
                    $lectQ->whereHas('supervisedStudents', function ($studQ) use ($user) {
                        $studQ->whereHas('evaluations', function ($evalQ) use ($user) {
                            $evalQ->whereHas('chairperson', function ($chairQ) use ($user) {
                                $chairQ->where('staff_number', $user->staff_number);
                            });
                        });
                    });
                })
                // Can see examiners of students they chair
                ->orWhereHas('lecturer', function ($lectQ) use ($user) {
                    $lectQ->whereHas('examinerEvaluations', function ($examQ) use ($user) {
                        $examQ->whereHas('student', function ($studQ) use ($user) {
                            $studQ->whereHas('evaluations', function ($evalQ) use ($user) {
                                $evalQ->whereHas('chairperson', function ($chairQ) use ($user) {
                                    $chairQ->where('staff_number', $user->staff_number);
                                });
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
        return $query->orderBy('created_at', 'desc')->paginate($numPerPage);
    }

    /**
     * Adds new User model into the database.
     * 
     * @param array $request
     * @throws \Exception
     * @return User
     */
    public function newUser(array $request): User 
    {
        try {
            // Start database transaction
            DB::beginTransaction();

            // Create new user entry
            $user = User::create([
                'staff_number' => $request['staff_number'],
                'name' => $request['name'],
                'email' => $request['email'],
                'password' => Hash::make( $request['staff_number']),
                'department' => $request['department'],
            ]);

            // Create corresponding lecturer entry
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

            // Link lecturer and user entries
            $user->lecturer_id = $lecturer->id;
            $user->save();
            $lecturer->user_id = $user->id;
            $lecturer->save();

            // Commit changes to database and return user instance
            DB::commit();
            return $user;

        } catch(Exception $e) {
            // If exception occurs, reverse made changes
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Updates User model info in the database.
     * 
     * @param int $id
     * @param array $request
     * @throws \Exception
     * @return User
     */
    public function updateUser($id, array $request): User 
    {
        try {
            // Start database transaction
            DB::beginTransaction();

            // Find user in database
            $user = User::find($id);
            if (!$user) {
                throw new Exception('Lecturer not found', 404);
            }
            
            // Update user info
            $user->name = $request['name'];
            $user->department = $request['department'];
            $user->email = $request['email'];
            $user->save();

            // Update corresponding lecturer entry info
            if(isset($user->lecturer_id)) {
                $lecturer = Lecturer::find($user->lecturer_id);
                
                $lecturer->name = $request['name'];
                $lecturer->email = $request['email'];
                $lecturer->department = $request['department'];
                $lecturer->is_from_fai = !($request['department'] == Department::OTHER);
                $lecturer->title = $request['title'];
                $lecturer->phone = $request['phone'];
                $lecturer->external_institution = $request['external_institution'];
                $lecturer->specialization = $request['specialization'];

                $lecturer->save();
            }

            // Commit changes to database and return user instance
            DB::commit();
            return $user;

        } catch (Exception $e) {
            // If exception occurs, reverse made changes
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Soft-deletes User model
     * 
     * @param int $id
     * @return void
     */
    public function deleteUser($id): void
    {
        DB::transaction(function () use ($id) {
            // Search and delete user entry from database
            // If lecturer is part of FAI, delete corresponding lecturer entry
            $user = User::findOrFail($id);
            if(isset($user->lecturer_id)) {
                Lecturer::find($user->lecturer_id)->delete();
            }
            $user->delete();
        });
    }
}
