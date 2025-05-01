<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Enums\UserRole;
use Carbon\Carbon;

class RoleTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = Carbon::now();

        // Define basic permissions for each role
        $permissions = [
            UserRole::OFFICE_ASSISTANT => json_encode([
                'users' => ['view'],
                'students' => ['view', 'create', 'edit'],
                'documents' => ['view', 'upload'],
            ]),
            UserRole::SUPERVISOR => json_encode([
                'users' => ['view'],
                'students' => ['view', 'create', 'edit', 'approve'],
                'research' => ['view', 'comment', 'approve'],
                'documents' => ['view', 'upload', 'approve'],
            ]),
            UserRole::PROGRAM_COORDINATOR => json_encode([
                'users' => ['view', 'create', 'edit'],
                'students' => ['view', 'create', 'edit', 'approve', 'delete'],
                'courses' => ['view', 'create', 'edit', 'delete'],
                'research' => ['view', 'comment', 'approve', 'reject'],
                'documents' => ['view', 'upload', 'approve', 'delete'],
                'reports' => ['view', 'generate'],
            ]),
            UserRole::PGAM => json_encode([
                'users' => ['view', 'create', 'edit', 'delete'],
                'students' => ['view', 'create', 'edit', 'approve', 'delete'],
                'courses' => ['view', 'create', 'edit', 'approve', 'delete'],
                'research' => ['view', 'comment', 'approve', 'reject'],
                'documents' => ['view', 'upload', 'approve', 'delete'],
                'reports' => ['view', 'generate', 'publish'],
                'settings' => ['view', 'edit'],
            ]),
        ];

        // Insert all roles from the UserRole enum
        foreach (UserRole::all() as $role) {
            DB::table('roles')->insert([
                'role_name' => $role,
                'description' => UserRole::getDescription($role),
                'permissions' => $permissions[$role],
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }
}