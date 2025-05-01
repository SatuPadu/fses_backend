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
                'name' => 'John Smith',
                'email' => 'officeassistant@example.com',
                'staff_number' => 'OA12345',
                'department' => $departments[0] ?? 'SEAT',
                'password' => Hash::make('OA12345'),
                'is_active' => true,
                'is_password_updated' => false,
            ],
            [
                'name' => 'Sarah Johnson',
                'email' => 'supervisor@example.com',
                'staff_number' => 'SV54321',
                'department' => $departments[1] ?? 'II',
                'password' => Hash::make('SV54321'),
                'is_active' => true,
                'is_password_updated' => false,
            ],
            [
                'name' => 'Michael Brown',
                'email' => 'coordinator@example.com',
                'staff_number' => 'PC78901',
                'department' => $departments[2] ?? 'BIHG',
                'password' => Hash::make('PC78901'),
                'is_active' => true,
                'is_password_updated' => false,
            ],
            [
                'name' => 'Dr. Emily Davis',
                'email' => 'pgam@example.com',
                'staff_number' => 'PG98765',
                'department' => $departments[3] ?? 'CAI',
                'password' => Hash::make('PG98765'),
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