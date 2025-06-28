<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Enums\UserRole;
use Carbon\Carbon;

class UserRoleTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        $now = Carbon::now();
        
        // Map each user to their corresponding role
        $userRoles = [
            // Office Assistant
            [
                'email' => 'officeassistant@utm.com',
                'role_name' => UserRole::OFFICE_ASSISTANT,
            ],
            // Research Supervisor
            [
                'email' => 'supervisor@utm.com',
                'role_name' => UserRole::SUPERVISOR,
            ],
            // Program Coordinator
            [
                'email' => 'coordinator@utm.com',
                'role_name' => UserRole::PROGRAM_COORDINATOR,
            ],
            // PGAM
            [
                'email' => 'pgam@utm.com',
                'role_name' => UserRole::PGAM,
            ],
        ];

        foreach ($userRoles as $mapping) {
            // Get user ID
            $user = DB::table('users')
                ->where('email', $mapping['email'])
                ->first();
                
            if (!$user) {
                continue;
            }
            
            // Get role ID
            $role = DB::table('roles')
                ->where('role_name', $mapping['role_name'])
                ->first();
                
            if (!$role) {
                continue; // Skip if role doesn't exist
            }
            
            // Create the relationship in the pivot table
            DB::table('user_roles')->insert([
                'user_id' => $user->id,
                'role_id' => $role->id,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }
}