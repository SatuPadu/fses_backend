<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Enums\Department;
use Carbon\Carbon;

class UserTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = Carbon::now();
        
        // Get departments from the Department enum
        $departments = Department::all();
        
        // Create only the Office Assistant user
        $users = [
            [
                'name' => 'Office Assistant',
                'email' => 'officeassistant@utm.com',
                'staff_number' => 'OA123456',
                'department' => $departments[0] ?? 'SEAT',
                'password' => Hash::make('OA123456'),
                'is_active' => true,
                'is_password_updated' => false,
            ],
        ];

        foreach ($users as $user) {
            DB::table('users')->insert(array_merge($user, [
                'lecturer_id' => null,
                'last_login' => null,
                'password_reset_token' => null,
                'password_reset_expiry' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]));
        }
    }
}