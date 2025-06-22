<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Modules\Auth\Models\User;
use App\Modules\UserManagement\Models\Role;
use App\Modules\UserManagement\Services\PermissionService;
use App\Enums\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;

class RolePermissionTest extends TestCase
{
    use RefreshDatabase;

    protected $permissionService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->permissionService = app(PermissionService::class);
    }

    /** @test */
    public function it_can_create_roles_with_permissions()
    {
        $role = Role::create([
            'role_name' => 'TestRole',
            'description' => 'Test role for testing',
            'permissions' => [
                'students' => ['view', 'create'],
                'users' => ['view']
            ]
        ]);

        $this->assertDatabaseHas('roles', [
            'role_name' => 'TestRole',
            'description' => 'Test role for testing'
        ]);

        $this->assertTrue($role->can('students', 'view'));
        $this->assertTrue($role->can('students', 'create'));
        $this->assertTrue($role->can('users', 'view'));
        $this->assertFalse($role->can('users', 'create'));
    }

    /** @test */
    public function it_can_assign_roles_to_users()
    {
        $user = User::create([
            'staff_number' => 'TEST123',
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
            'department' => 'SEAT',
            'is_active' => true,
            'is_password_updated' => true
        ]);

        $role = Role::create([
            'role_name' => 'TestRole',
            'description' => 'Test role',
            'permissions' => [
                'students' => ['view', 'create']
            ]
        ]);

        $user->roles()->attach($role->id);

        $this->assertTrue($user->hasRole('TestRole'));
        $this->assertTrue($user->hasPermissionFor('students', 'view'));
        $this->assertTrue($user->hasPermissionFor('students', 'create'));
        $this->assertFalse($user->hasPermissionFor('students', 'delete'));
    }

    /** @test */
    public function it_can_check_user_permissions_through_service()
    {
        $user = User::create([
            'staff_number' => 'TEST123',
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
            'department' => 'SEAT',
            'is_active' => true,
            'is_password_updated' => true
        ]);

        $role = Role::create([
            'role_name' => 'Supervisor',
            'description' => 'Research Supervisor',
            'permissions' => [
                'students' => ['view'],
                'evaluations' => ['view', 'nominate']
            ]
        ]);

        $user->roles()->attach($role->id);

        $this->assertTrue($this->permissionService->userCan($user, 'students', 'view'));
        $this->assertTrue($this->permissionService->userCan($user, 'evaluations', 'nominate'));
        $this->assertFalse($this->permissionService->userCan($user, 'students', 'delete'));
        $this->assertTrue($this->permissionService->userHasSpecificRole($user, 'Supervisor'));
    }

    /** @test */
    public function it_can_get_user_permissions()
    {
        $user = User::create([
            'staff_number' => 'TEST123',
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
            'department' => 'SEAT',
            'is_active' => true,
            'is_password_updated' => true
        ]);

        $role = Role::create([
            'role_name' => 'TestRole',
            'description' => 'Test role',
            'permissions' => [
                'students' => ['view', 'create'],
                'users' => ['view']
            ]
        ]);

        $user->roles()->attach($role->id);

        $permissions = $user->getAllPermissions();

        $this->assertArrayHasKey('students', $permissions);
        $this->assertArrayHasKey('users', $permissions);
        $this->assertContains('view', $permissions['students']);
        $this->assertContains('create', $permissions['students']);
        $this->assertContains('view', $permissions['users']);
    }

    /** @test */
    public function it_can_handle_multiple_roles_per_user()
    {
        $user = User::create([
            'staff_number' => 'TEST123',
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
            'department' => 'SEAT',
            'is_active' => true,
            'is_password_updated' => true
        ]);

        $role1 = Role::create([
            'role_name' => 'Role1',
            'description' => 'First role',
            'permissions' => [
                'students' => ['view']
            ]
        ]);

        $role2 = Role::create([
            'role_name' => 'Role2',
            'description' => 'Second role',
            'permissions' => [
                'users' => ['view'],
                'students' => ['create']
            ]
        ]);

        $user->roles()->attach([$role1->id, $role2->id]);

        $this->assertTrue($user->hasAnyRole(['Role1', 'Role2']));
        $this->assertTrue($user->hasPermissionFor('students', 'view'));
        $this->assertTrue($user->hasPermissionFor('students', 'create'));
        $this->assertTrue($user->hasPermissionFor('users', 'view'));

        $permissions = $user->getAllPermissions();
        $this->assertContains('view', $permissions['students']);
        $this->assertContains('create', $permissions['students']);
    }

    /** @test */
    public function it_can_use_role_scopes()
    {
        Role::create([
            'role_name' => 'Supervisor',
            'description' => 'Research Supervisor',
            'permissions' => ['students' => ['view']]
        ]);

        Role::create([
            'role_name' => 'ProgramCoordinator',
            'description' => 'Program Coordinator',
            'permissions' => ['students' => ['view', 'create']]
        ]);

        $supervisorRoles = Role::byName('Supervisor')->get();
        $this->assertCount(1, $supervisorRoles);
        $this->assertEquals('Supervisor', $supervisorRoles->first()->role_name);

        $rolesWithStudentPermission = Role::withModulePermission('students')->get();
        $this->assertCount(2, $rolesWithStudentPermission);
    }

    /** @test */
    public function it_can_use_user_scopes()
    {
        $user1 = User::create([
            'staff_number' => 'TEST1',
            'name' => 'Test User 1',
            'email' => 'test1@example.com',
            'password' => bcrypt('password'),
            'department' => 'SEAT',
            'is_active' => true,
            'is_password_updated' => true
        ]);

        $user2 = User::create([
            'staff_number' => 'TEST2',
            'name' => 'Test User 2',
            'email' => 'test2@example.com',
            'password' => bcrypt('password'),
            'department' => 'II',
            'is_active' => false,
            'is_password_updated' => true
        ]);

        $role = Role::create([
            'role_name' => 'Supervisor',
            'description' => 'Research Supervisor',
            'permissions' => ['students' => ['view']]
        ]);

        $user1->roles()->attach($role->id);
        $user2->roles()->attach($role->id);

        $activeUsers = User::active()->get();
        $this->assertCount(1, $activeUsers);
        $this->assertEquals('TEST1', $activeUsers->first()->staff_number);

        $seatUsers = User::byDepartment('SEAT')->get();
        $this->assertCount(1, $seatUsers);
        $this->assertEquals('SEAT', $seatUsers->first()->department);

        $supervisorUsers = User::byRole('Supervisor')->get();
        $this->assertCount(2, $supervisorUsers);
    }
} 