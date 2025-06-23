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
        $this->command->info('Seeding roles with permissions...');

        $now = Carbon::now();
        $roles = $this->getRoleDefinitions();

        foreach ($roles as $role) {
            DB::table('roles')->insert([
                'role_name' => $role['name'],
                'description' => $role['description'],
                'permissions' => json_encode($role['permissions']),
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $this->command->info("✓ Created role: {$role['name']}");
        }

        $this->command->info('Roles seeded successfully!');
    }

    /**
     * Get role definitions with permissions
     */
    private function getRoleDefinitions(): array
    {
        return [
            [
                'name' => UserRole::OFFICE_ASSISTANT,
                'description' => UserRole::getDescription(UserRole::OFFICE_ASSISTANT),
                'permissions' => [
                    'students' => ['view', 'create', 'edit', 'delete', 'import'],
                    'users' => ['view', 'create', 'edit'],
                    'lecturers' => ['view', 'create', 'edit', 'delete'],
                    'programs' => ['view'],
                ]
            ],
            [
                'name' => UserRole::SUPERVISOR,
                'description' => UserRole::getDescription(UserRole::SUPERVISOR),
                'permissions' => [
                    'students' => ['view'],
                    'evaluations' => ['view', 'nominate', 'modify'],
                    'nominations' => ['view', 'create', 'edit', 'postpone'],
                ]
            ],
            [
                'name' => UserRole::CO_SUPERVISOR,
                'description' => UserRole::getDescription(UserRole::CO_SUPERVISOR),
                'permissions' => [
                    'students' => ['view'],
                    'evaluations' => ['view', 'nominate', 'modify'],
                    'nominations' => ['view', 'create', 'edit', 'postpone'],
                ]
            ],
            [
                'name' => UserRole::PROGRAM_COORDINATOR,
                'description' => UserRole::getDescription(UserRole::PROGRAM_COORDINATOR),
                'permissions' => [
                    'students' => ['view', 'create', 'edit', 'delete', 'export'],
                    'users' => ['view', 'create', 'edit', 'delete'],
                    'lecturers' => ['view', 'create', 'edit', 'delete'],
                    'programs' => ['view', 'create', 'edit', 'delete'],
                    'evaluations' => ['view', 'assign', 'lock'],
                    'nominations' => ['view', 'lock'],
                    'chairpersons' => ['view', 'assign', 'modify'],
                    'reports' => ['view', 'generate', 'download'],
                ]
            ],
            [
                'name' => UserRole::CHAIRPERSON,
                'description' => UserRole::getDescription(UserRole::CHAIRPERSON),
                'permissions' => [
                    'students' => ['view'],
                    'evaluations' => ['view', 'conduct'],
                    'reports' => ['view'],
                ]
            ],
            [
                'name' => UserRole::PGAM,
                'description' => UserRole::getDescription(UserRole::PGAM),
                'permissions' => [
                    'students' => ['view', 'create', 'edit', 'delete', 'export'],
                    'users' => ['view', 'create', 'edit', 'delete'],
                    'lecturers' => ['view', 'create', 'edit', 'delete'],
                    'programs' => ['view', 'create', 'edit', 'delete'],
                    'evaluations' => ['view', 'assign', 'lock'],
                    'nominations' => ['view', 'lock'],
                    'chairpersons' => ['view', 'assign', 'modify'],
                    'reports' => ['view', 'generate', 'download', 'publish'],
                    'settings' => ['view', 'edit'],
                ]
            ],
        ];
    }
}