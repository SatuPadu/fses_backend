<?php

namespace App\Modules\UserManagement\Services;

use DB;
use App\Enums\Department;
use App\Modules\Auth\Models\User;
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
        return Lecturer::all();
    }

    /**
     * Adds new Lecturer/User model into the database.
     * 
     * @param array $request
     * @throws \Exception
     * @return array{lecturer_id: mixed}
     */
    public function newLecturer(array $request): array 
    {
        $new_lecturer = DB::transaction(function () use ($request) {
            $lecturer = Lecturer::create([
                'name' => $request['name'],
                'email' => $request['email'],
                'phone' => $request['phone'],
                'department' => $request['department'],
                'title' => $request['title'],
                'is_from_fai' => !($request['department'] == Department::OTHER),
                'external_institution' => $request['external_institution'],
                'specialization' => $request['specialization'],
            ]);

            $lecturer->user()->create([
                'staff_number' => $request['staff_number'],
                'name' => $request['name'],
                'email' => $request['email'],
                'password' => Hash::make($request['staff_number']),
                'department' => $request['department'],
            ]);

            return $lecturer;
        });

        if (!$new_lecturer) {
            throw new \Exception('Something went wrong!');
        }

        return ['lecturer_id' => $new_lecturer->id];
    }

    /**
     * Updates Lecturer/User model info in the database.
     * 
     * @param int $id
     * @param array $request
     * @throws \Exception
     * @return void
     */
    public function updateLecturer($id, array $request): void 
    {
        $updated_lecturer = DB::transaction(function () use ($id, $request){
            $lecturer = Lecturer::findOrFail(User::findOrFail($id)->lecturer_id);

            $lecturer->name = $request['name'];
            $lecturer->title = $request['title'];
            $lecturer->department = $request['department'];
            $lecturer->external_institution = $request['external_institution'];
            $lecturer->is_from_fai = !($request['department'] == Department::OTHER);
            $lecturer->specialization = $request['specialization'];
            $lecturer->email = $request['email'];
            $lecturer->phone = $request['phone'];

            $lecturer->user->staff_number = $request['staff_number'];
            $lecturer->user->name = $request['name'];
            $lecturer->user->email = $request['email'];
            $lecturer->user->department = $request['department'];
            if(!($lecturer->user->is_password_updated)) {
                $lecturer->user->password = Hash::make($request['staff_number']);
            }

            $lecturer->push();

            return $lecturer;
        });

        if (!$updated_lecturer) {
            throw new \Exception('Failed to update lecturer info!');
        }
    }

    /**
     * Soft-deletes Lecturer/User model
     * 
     * @param int $id
     * @return void
     */
    public function deleteLecturer($id): void 
    {
        DB::transaction(function () use ($id) {
            $lecturer = Lecturer::findOrFail(User::findOrFail($id)->lecturer_id);
            $lecturer->user()->delete();
            $lecturer->delete();
        });
    }
}
