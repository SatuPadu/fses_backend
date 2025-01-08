<?php
namespace Tests\Feature;

use Tests\TestCase;
use RuntimeException;
use App\Modules\Articles\Services\TopicService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TopicControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_fetch_all_topics()
    {
        // Mock the service response
        $this->mock(TopicService::class, function ($mock) {
            $mock->shouldReceive('getTopics')->andReturn([
                ['id' => 1, 'name' => 'Technology'],
                ['id' => 2, 'name' => 'Health'],
            ]);
        });

        $response = $this->getJson('/api/article/topics');

        $response->assertStatus(200)
                 ->assertJsonFragment(['name' => 'Technology']);
    }

    /** @test */
    public function it_handles_runtime_exceptions_when_fetching_topics()
    {
        $this->mock(TopicService::class, function ($mock) {
            $mock->shouldReceive('getTopics')->andThrow(new RuntimeException('Runtime error'));
        });

        $response = $this->getJson('/api/article/topics');

        $response->assertStatus(500)
                 ->assertJsonFragment(['message' => 'Runtime error']);
    }

    /** @test */
    public function it_handles_general_exceptions_when_fetching_topics()
    {
        $this->mock(TopicService::class, function ($mock) {
            $mock->shouldReceive('getTopics')->andThrow(new \Exception('Unexpected error'));
        });

        $response = $this->getJson('/api/article/topics');

        $response->assertStatus(500)
                ->assertJson([
                    'success' => false,
                    'message' => 'Failed to fetch topics. Please try again later.',
                    'data' => ['error' => 'Unexpected error'],
                ]);
    }
}