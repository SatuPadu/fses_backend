<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Modules\Auth\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AuthFeatureTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_registers_a_user_with_valid_data()
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'John Doe',
            'email' => 'john.doe@user.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(201)
            ->assertJsonFragment([
                'success' => true,
                'message' => 'User registered successfully.',
            ])
            ->assertJsonStructure([
                'data' => [
                    'user' => ['id', 'name', 'email'],
                    'token',
                ],
            ]);

        $this->assertDatabaseHas('users', ['email' => 'john.doe@user.com']);
    }

    /** @test */
    public function it_returns_validation_error_for_missing_fields()
    {
        $response = $this->postJson('/api/auth/register', [
            'email' => 'john.doe@user.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(422)
            ->assertJsonFragment(['success' => false, 'message' => 'Validation Error'])
            ->assertJsonPath('data.name.0', 'The name field is required.')
            ->assertJsonPath('data.password_confirmation.0', 'The password confirmation field is required.');
    }

    /** @test */
    public function it_rejects_duplicate_email_registration()
    {
        User::factory()->create(['email' => 'john.doe@user.com']);

        $response = $this->postJson('/api/auth/register', [
            'name' => 'John Doe',
            'email' => 'john.doe@user.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(422)
            ->assertJsonFragment(['success' => false, 'message' => 'Validation Error'])
            ->assertJsonPath('data.email.0', 'The email has already been taken.');
    }

    /** @test */
    public function it_rejects_weak_passwords()
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'John Doe',
            'email' => 'john.doe@user.com',
            'password' => '12345',
            'password_confirmation' => '12345',
        ]);

        $response->assertStatus(422)
            ->assertJsonFragment(['success' => false, 'message' => 'Validation Error'])
            ->assertJsonPath('data.password.0', 'The password field must be at least 8 characters.');
    }

    /** @test */
    public function it_logs_in_a_user_with_valid_credentials()
    {
        $user = User::factory()->create(['password' => bcrypt('password123')]);

        $response = $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJsonFragment([
                'success' => true,
                'message' => 'User logged in successfully.',
            ])
            ->assertJsonStructure([
                'data' => [
                    'user' => ['id', 'name', 'email'],
                    'token',
                ],
            ]);
    }

    /** @test */
    public function it_rejects_login_with_invalid_password()
    {
        $user = User::factory()->create(['password' => bcrypt('password123')]);

        $response = $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(401)
            ->assertJson(['message' => 'Invalid credentials']);
    }

    /** @test */
    public function it_rejects_login_for_nonexistent_user()
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => 'nonexistent@user.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(401)
            ->assertJson(['message' => 'Invalid credentials']);
    }

    /** @test */
    public function it_sends_reset_link_for_valid_email()
    {
        $user = User::factory()->create(['email' => 'john.doe@user.com']);

        $response = $this->postJson('/api/password/reset-link', [
            'email' => 'john.doe@user.com',
        ]);

        $response->assertStatus(200)
            ->assertJson(['message' => 'Password reset link sent.']);
    }

    /** @test */
    public function it_rejects_invalid_email_for_password_reset_link()
    {
        $response = $this->postJson('/api/password/reset-link', [
            'email' => 'nonexistent@user.com',
        ]);

        $response->assertStatus(404)
            ->assertJson(['message' => 'Invalid email address.']);
    }

    /** @test */
    public function it_resets_password_with_valid_data()
    {
        $user = User::factory()->create([
            'email' => 'john.doe@user.com',
            'password' => bcrypt('oldpassword123'),
        ]);

        $token = 'valid-reset-token';
        DB::table('password_resets')->insert([
            'email' => $user->email,
            'token' => Hash::make($token),
            'created_at' => now(),
            'expires_at' => now()->addMinutes(config('auth.passwords.users.expire', 60)), // Include expiry
        ]);

        $response = $this->postJson('/api/password/reset', [
            'token' => $token,
            'email' => $user->email,
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertStatus(200)
            ->assertJson(['message' => 'Password reset successful.']);

        $this->assertTrue(Hash::check('newpassword123', $user->fresh()->password));
    }

    /** @test */
    public function it_rejects_invalid_reset_tokens()
    {
        $user = User::factory()->create(['email' => 'john.doe@user.com']);

        $response = $this->postJson('/api/password/reset', [
            'token' => 'invalid-token',
            'email' => $user->email,
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertStatus(400)
            ->assertJson(['message' => 'Invalid or expired token.']);
    }
}