<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Modules\Auth\Models\User;
use App\Modules\UserManagement\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tymon\JWTAuth\Facades\JWTAuth;

class JWTMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    public function test_jwt_middleware_allows_active_user()
    {
        // Create an active user
        $user = User::factory()->create([
            'is_active' => true,
            'is_password_updated' => true
        ]);

        // Create a role and assign to user
        $role = Role::factory()->create(['role_name' => 'OfficeAssistant']);
        $user->roles()->attach($role->id);

        // Generate token for the user
        $token = JWTAuth::fromUser($user);

        // Make request with token
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->get('/api/auth/auth-user');

        // Should allow access
        $response->assertStatus(200);
    }

    public function test_jwt_middleware_blocks_inactive_user()
    {
        // Create an inactive user
        $user = User::factory()->create([
            'is_active' => false,
            'is_password_updated' => true
        ]);

        // Create a role and assign to user
        $role = Role::factory()->create(['role_name' => 'OfficeAssistant']);
        $user->roles()->attach($role->id);

        // Generate token for the user
        $token = JWTAuth::fromUser($user);

        // Make request with token
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->get('/api/auth/auth-user');

        // Should block access
        $response->assertStatus(403);
        $response->assertJson([
            'status' => 'error',
            'message' => 'User account is deactivated',
            'error_type' => 'account_deactivated'
        ]);
    }

    public function test_jwt_middleware_blocks_invalid_token()
    {
        // Make request with invalid token
        $response = $this->withHeaders([
            'Authorization' => 'Bearer invalid_token',
        ])->get('/api/auth/auth-user');

        // Should block access
        $response->assertStatus(401);
        $response->assertJson([
            'status' => 'error',
            'error_type' => 'token_invalid'
        ]);
    }

    public function test_jwt_middleware_blocks_missing_token()
    {
        // Make request without token
        $response = $this->get('/api/auth/auth-user');

        // Should block access
        $response->assertStatus(401);
    }
} 