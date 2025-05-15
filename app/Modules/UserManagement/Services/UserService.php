<?php

namespace App\Modules\UserManagement\Services;

use DB;
use App\Enums\Department;
use App\Modules\Auth\Models\User;
use Exception;
use Illuminate\Support\Facades\Hash;
use App\Modules\UserManagement\Models\Lecturer;

class UserService 
{
    /**
     * Returns all non-deleted Lecturer models from the database.
     * 
     * @return \Illuminate\Database\Eloquent\Collection<int, Lecturer>
     */
    public function getLecturers()
    {
        return Lecturer::with('user')->get();
    }

    /**
     * Adds new Lecturer/User model into the database.
     * 
     * @param array $request
     * @throws \Exception
     * @return array{lecturer_id: mixed}
     */
    public function newLecturer(array $request): Lecturer 
    {
        try{
            
            DB::beginTransaction();

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

            DB::commit();
            return $lecturer;

        } catch(Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Updates Lecturer/User model info in the database.
     * 
     * @param int $id
     * @param array $request
     * @throws \Exception
     * @return void
     */
    public function updateLecturer($id, array $request): Lecturer 
    {
        try {
            DB::beginTransaction();

            $lecturer = Lecturer::find($id);
            
            if (!$lecturer) {
                throw new Exception('Lecturer not found', 404);
            }
                $lecturer->name = $request['name'];
                $lecturer->title = $request['title'];
                $lecturer->department = $request['department'];
                $lecturer->external_institution = $request['external_institution'];
                $lecturer->is_from_fai = !($request['department'] == Department::OTHER);
                $lecturer->specialization = $request['specialization'];
                $lecturer->email = $request['email'];
                $lecturer->phone = $request['phone'];
                $lecturer->save();

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

            DB::commit();

            return $lecturer;
        } catch (Exception $e) {
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
            $lecturer = Lecturer::findOrFail($id);
            if($lecturer->is_from_fai) {
                User::find($lecturer->user_id)->delete();
            }
            $lecturer->delete();
        });
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
            $user = User::findOrFail($id);
            if(isset($user->lecturer_id)) {
                Lecturer::find($user->lecturer_id)->delete();
            }
            $user->delete();
        });
    }
}
