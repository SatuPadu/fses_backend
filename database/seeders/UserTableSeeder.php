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
        
        // Create one user for each role, rotating through departments
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
            [
                'name' => 'Supervisor',
                'email' => 'supervisor@utm.com',
                'staff_number' => 'SV654321',
                'department' => $departments[1] ?? 'II',
                'password' => Hash::make('SV654321'),
                'is_active' => true,
                'is_password_updated' => false,
            ],
            [
                'name' => 'Program Coordinator',
                'email' => 'coordinator@utm.com',
                'staff_number' => 'PC678901',
                'department' => $departments[2] ?? 'BIHG',
                'password' => Hash::make('PC678901'),
                'is_active' => true,
                'is_password_updated' => false,
            ],
            [
                'name' => 'PGAM Administrator',
                'email' => 'pgam@utm.com',
                'staff_number' => 'PG987654',
                'department' => $departments[3] ?? 'CAI',
                'password' => Hash::make('PG987654'),
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