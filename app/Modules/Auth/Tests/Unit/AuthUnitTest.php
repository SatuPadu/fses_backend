<?php

namespace Modules\Auth\Tests\Unit;

use Tests\TestCase;
use App\Modules\Auth\Models\User;
use Illuminate\Support\Facades\Hash;
use Mockery;
use Dotenv\Dotenv;
use App\Modules\Auth\Models\PasswordReset;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\HttpKernel\Exception\HttpException;

class AuthUnitTest extends TestCase
{

    use RefreshDatabase;
    
    /**
     * @var \App\Modules\Auth\Services\AuthService
     */
    protected $authService;

    /**
     * @var \App\Modules\Auth\Services\PasswordResetService
     */
    protected $passwordResetService;

    /**
     * @var \Mockery\LegacyMockInterface&\App\Modules\Auth\Repositories\UserRepository
     */
    protected $userRepository;

    /**
     * @var \Mockery\LegacyMockInterface&\App\Modules\Auth\Repositories\PasswordResetRepository
     */
    protected $passwordResetRepo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate:fresh');

        // Mock UserRepository
        $this->userRepository = Mockery::mock('App\Modules\Auth\Repositories\UserRepository');

        // Mock PasswordResetRepository
        $this->passwordResetRepo = Mockery::mock('App\Modules\Auth\Repositories\PasswordResetRepository');

        // Initialize the AuthService with mocked repositories
        $this->authService = new \App\Modules\Auth\Services\AuthService(
            $this->userRepository,
            $this->passwordResetRepo
        );

        // Initialize the PasswordResetService with mocked repository
        $this->passwordResetService = new \App\Modules\Auth\Services\PasswordResetService(
            $this->passwordResetRepo,
            $this->userRepository
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function it_registers_a_user_with_valid_data()
    {
        $data = [
            'name' => 'John Doe',
            'email' => 'john.doe@user.com',
            'password' => 'password123',
        ];

        // Mocked User object
        $mockedUser = Mockery::mock(User::class)->makePartial();
        $mockedUser->id = 1;
        $mockedUser->name = $data['name'];
        $mockedUser->email = $data['email'];
        $mockedUser->password = Hash::make($data['password']); // Use Hash::make to match repository behavior

        // Mock createToken to return a valid object
        $mockedToken = Mockery::mock();
        $mockedToken->plainTextToken = 'mocked-token';

        $mockedUser->shouldReceive('createToken')
            ->once()
            ->with($data['email'])
            ->andReturn($mockedToken);

        // Mock the findByEmail method to return null (no existing user)
        $this->userRepository->shouldReceive('findByEmail')
            ->once()
            ->with($data['email'])
            ->andReturn(null);

        // Pre-hash the password to simulate repository behavior
        $hashedPassword = Hash::make($data['password']);

        // Mock the create method with pre-hashed password
        $this->userRepository->shouldReceive('create')
            ->once()
            ->with(Mockery::on(function ($input) use ($data, $hashedPassword) {
                // Validate input fields
                return $input['name'] === $data['name']
                    && $input['email'] === $data['email']
                    && Hash::check($data['password'], $hashedPassword);
            }))
            ->andReturn($mockedUser);

        // Call the service method
        $result = $this->authService->register($data);

        // Assertions
        $this->assertArrayHasKey('user', $result);
        $this->assertArrayHasKey('token', $result);
        $this->assertEquals('mocked-token', $result['token']);
        $this->assertEquals($mockedUser->email, $result['user']->email);
        $this->assertTrue(Hash::check($data['password'], $result['user']->password)); // Validate hashed password
    }

    /** @test */
    public function it_rejects_duplicate_email_registration()
    {
        $data = [
            'name' => 'John Doe',
            'email' => 'john.doe@user.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        $this->userRepository->shouldReceive('findByEmail')
            ->once()
            ->with($data['email'])
            ->andReturn(true);

        $this->expectException(HttpException::class);
        $this->authService->register($data);
    }

    /** @test */
    public function it_logs_in_a_user_with_valid_credentials()
    {
        // Create a mock User model
        $user = Mockery::mock(User::class)->makePartial();
        $user->id = 1; // Simulate a valid ID
        $user->email = 'john.doe@user.com';
        $user->password = bcrypt('password123');

        $user->shouldReceive('createToken')
            ->once()
            ->with($user->email)
            ->andReturn((object)['plainTextToken' => 'mocked-token']);

        $data = [
            'email' => 'john.doe@user.com',
            'password' => 'password123',
        ];

        $this->userRepository->shouldReceive('findByEmail')
            ->once()
            ->with($data['email'])
            ->andReturn($user);

        $result = $this->authService->login($data);

        // Assertions
        $this->assertArrayHasKey('user', $result);
        $this->assertArrayHasKey('token', $result);
        $this->assertEquals('mocked-token', $result['token']);
    }

    /** @test */
    public function it_rejects_login_with_invalid_password()
    {
        $user = new User([
            'email' => 'john.doe@user.com',
            'password' => bcrypt('password123'),
        ]);

        $data = [
            'email' => 'john.doe@user.com',
            'password' => 'wrongpassword',
        ];

        $this->userRepository->shouldReceive('findByEmail')
            ->once()
            ->with($data['email'])
            ->andReturn($user);

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $this->authService->login($data);
    }

    /** @test */
    public function it_resets_password_with_valid_data()
    {
        // Use RefreshDatabase to ensure a clean slate (if not already done globally)
        $this->artisan('migrate:fresh');

        // Create and persist the user with a hashed password
        $user = User::factory()->create([
            'email' => 'john.doe@user.com',
            'password' => Hash::make('oldpassword123'), // Properly hash the old password
        ]);

        $token = 'valid-reset-token';

        $data = [
            'email' => 'john.doe@user.com',
            'token' => $token,
            'password' => 'newpassword123',
        ];

        // Create a valid password reset record
        $resetRecord = PasswordReset::create([
            'email' => $data['email'],
            'token' => Hash::make($token),
            'expires_at' => now()->addMinutes(60),
        ]);

        $this->userRepository->shouldReceive('findByEmail')
            ->once()
            ->with($data['email'])
            ->andReturn($user);

        $this->passwordResetRepo->shouldReceive('findByToken')
            ->once()
            ->with($data['email'], $data['token'])
            ->andReturn($resetRecord);

        $this->passwordResetRepo->shouldReceive('delete')
            ->once()
            ->with($resetRecord);

        $this->passwordResetService->resetPassword($data);

        // Reload the user from the database to verify the password has been updated
        $user->refresh();

        // Assert the password has been updated and properly hashed
        $this->assertTrue(Hash::check($data['password'], $user->password));
    }

    /** @test */
    public function it_rejects_invalid_reset_tokens()
    {
        $data = [
            'email' => 'john.doe@user.com',
            'token' => 'invalid-token',
            'password' => 'newpassword123',
        ];

        // Mock the repository to return null for an invalid token
        $this->passwordResetRepo->shouldReceive('findByToken')
            ->once()
            ->with($data['email'], $data['token'])
            ->andReturn(null);

        // Expect the HttpException for invalid token
        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('Invalid or expired token.');

        $this->passwordResetService->resetPassword($data);
    }
}