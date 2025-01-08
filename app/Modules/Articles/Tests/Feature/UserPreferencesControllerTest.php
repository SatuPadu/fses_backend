<?php
namespace Tests\Feature;

use Tests\TestCase;
use App\Modules\Auth\Models\User;
use App\Modules\Articles\Models\Article;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Modules\Articles\Services\UserPreferencesService;

class UserPreferencesControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_save_valid_preferences()
    {
        // Create a user
        $user = User::factory()->create();

        // Seed valid topics, sources, and authors in the database
        Article::factory()->create(['topic' => 'technology', 'source_name' => 'Source 1', 'author' => 'Author A']);
        Article::factory()->create(['topic' => 'health', 'source_name' => 'Source 2', 'author' => 'Author B']);

        // Authenticate the user
        $this->actingAs($user, 'sanctum');

        // Send valid preferences data
        $response = $this->postJson('/api/preferences/set-preferences', [
            'topics' => ['technology', 'health'],
            'sources' => ['Source 1', 'Source 2'],
            'authors' => ['Author A', 'Author B'],
        ]);

        // Assert successful response
        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Preferences updated successfully.',
                ]);
    }

    /** @test */
    public function it_returns_error_for_invalid_preferences()
    {
        // Create a user
        $user = User::factory()->create();

        // Authenticate the user
        $this->actingAs($user, 'sanctum');

        // Send invalid data for preferences
        $response = $this->postJson('/api/preferences/set-preferences', [
            'topics' => ['invalid topic'],
        ]);

        // Assert error response for invalid preferences
        $response->assertStatus(422)
                ->assertJsonFragment([
                    'success' => false,
                    'message' => 'Invalid preferences provided.',
                ])
                ->assertJsonPath('data.error', 'Invalid topic: invalid topic')
                ->assertJsonStructure([
                    'success',
                    'message',
                    'data' => [
                        'error',
                    ],
                ]);
    }

    /** @test */
    public function it_handles_unexpected_errors_when_saving_preferences()
    {
        // Create a user
        $user = User::factory()->create();

        // Authenticate the user
        $this->actingAs($user, 'sanctum');

        // Mock the service to throw an exception
        $this->mock(UserPreferencesService::class, function ($mock) {
            $mock->shouldReceive('setPreferences')->andThrow(new \Exception('Unexpected error'));
        });

        // Send a POST request with valid data
        $response = $this->postJson('/api/preferences/set-preferences', [
            'topics' => ['technology'],
        ]);

        // Assert the response status and structure
        $response->assertStatus(500)
                ->assertJsonFragment([
                    'success' => false,
                    'message' => 'Failed to save preferences due to an unexpected error. Please try again later.',
                ])
                ->assertJsonPath('data.error', 'Unexpected error') // Ensure the specific error is included
                ->assertJsonStructure([
                    'success',
                    'message',
                    'data' => ['error'],
                ]);
    }

    /** @test */
    public function it_can_retrieve_preferences_for_authenticated_user()
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'sanctum');

        $this->mock(UserPreferencesService::class, function ($mock) {
            $mock->shouldReceive('getPreferences')->andReturn([
                'topics' => ['technology'],
                'sources' => ['Source 1'],
                'authors' => ['Author A'],
            ]);
        });

        $response = $this->getJson('/api/preferences');

        $response->assertStatus(200)
                 ->assertJsonFragment(['topics' => ['technology']]);
    }

    /** @test */
    public function it_returns_unauthorized_error_for_unauthenticated_user()
    {
        $response = $this->getJson('/api/preferences');

        $response->assertStatus(401)
                 ->assertJson(['message' => 'Please login to continue.']);
    }

    /** @test */
    public function it_handles_unexpected_errors_when_retrieving_preferences()
    {
        // Create a user
        $user = User::factory()->create();

        // Authenticate the user
        $this->actingAs($user, 'sanctum');

        // Mock the service to throw an exception
        $this->mock(UserPreferencesService::class, function ($mock) {
            $mock->shouldReceive('getPreferences')->andThrow(new \Exception('Unexpected error'));
        });

        // Send a GET request to retrieve preferences
        $response = $this->getJson('/api/preferences');

        // Assert the response status and structure
        $response->assertStatus(500)
                ->assertJsonFragment([
                    'success' => false,
                    'message' => 'Failed to retrieve preferences. Please try again later.',
                ])
                ->assertJsonStructure([
                    'success',
                    'message',
                    'data' => ['error'],
                ]);
    }
}