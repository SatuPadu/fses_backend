<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Modules\Auth\Models\User;
use App\Modules\Articles\Models\Article;
use  App\Modules\Articles\Services\ArticleService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ArticleControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_fetch_article_by_valid_id()
    {
        // Create an article using the factory
        $article = Article::factory()->create();

        $response = $this->getJson("/api/article/detail/{$article->id}");

        $response->assertStatus(200)
                 ->assertJsonFragment([
                     'id' => $article->id,
                     'title' => $article->title,
                 ]);
    }

    /** @test */
    public function it_returns_404_for_invalid_article_id()
    {
        $response = $this->getJson('/api/article/detail/999');

        $response->assertStatus(404)
                 ->assertJson(['message' => 'Article not found.']);
    }

    /** @test */
    public function it_can_fetch_sources_by_topics()
    {
        // Create a user
        $user = User::factory()->create();

        // Authenticate the user
        $this->actingAs($user, 'sanctum');

        // Seed the database with articles
        Article::factory()->count(3)->create([
            'topic' => 'Technology',
            'source_name' => 'TechCrunch',
        ]);
        Article::factory()->count(2)->create([
            'topic' => 'Health',
            'source_name' => 'Healthline',
        ]);

        // Use array syntax for topics in the query string
        $response = $this->getJson('/api/preferences/sources?topics[]=Technology&topics[]=Health');

        $response->assertStatus(200)
             ->assertJsonFragment([
                 'success' => true,
                 'message' => 'Sources fetched successfully.',
             ])
             ->assertJsonStructure([
                 'success',
                 'data',
                 'message',
             ])
             ->assertJsonFragment([
                 'data' => ['TechCrunch', 'Healthline'],
             ]);
    }

    /** @test */
    public function it_returns_400_if_topics_are_missing()
    {
        // Create a user
        $user = User::factory()->create();

        // Authenticate the user
        $this->actingAs($user, 'sanctum');
    
        $response = $this->getJson('/api/preferences/sources');

        $response->assertStatus(400)
                 ->assertJson(['message' => 'Topics are required.']);
    }

    /** @test */
    public function it_can_fetch_authors_by_topics_and_sources()
    {
        // Create a user
        $user = User::factory()->create();

        // Authenticate the user
        $this->actingAs($user, 'sanctum');

        // Seed the database with articles
        Article::factory()->count(3)->create([
            'topic' => 'Technology',
            'source_name' => 'TechCrunch',
            'author' => 'Alice Tech',
        ]);
        Article::factory()->count(2)->create([
            'topic' => 'Health',
            'source_name' => 'Healthline',
            'author' => 'Dr. Healthy',
        ]);

        // Use array syntax for topics and sources in the query string
        $response = $this->getJson('/api/preferences/authors?topics[]=Technology&topics[]=Health&sources[]=TechCrunch&sources[]=Healthline');

        $response->assertStatus(200)
                ->assertJsonFragment([
                    'success' => true,
                    'message' => 'Authors fetched successfully.',
                ])
                ->assertJsonStructure([
                    'success',
                    'data',
                    'message',
                ])
                ->assertJsonFragment([
                    'data' => ['Alice Tech', 'Dr. Healthy'],
                ]);
    }

    /** @test */
    public function it_handles_unexpected_errors_when_fetching_article()
    {
        // Create a user
        $user = User::factory()->create();

        // Authenticate the user
        $this->actingAs($user, 'sanctum');
        
        // Simulate an error during article retrieval
        $this->mock(ArticleService::class, function ($mock) {
            $mock->shouldReceive('getArticleById')->andThrow(new \Exception('Unexpected error'));
        });

        $response = $this->getJson('/api/article/detail/1');

        $response->assertStatus(500)
                 ->assertJsonFragment(['message' => 'Failed to fetch the article. Please try again later.']);
    }

    /** @test */
    public function it_handles_unexpected_errors_when_fetching_sources()
    {
        // Create a user
        $user = User::factory()->create();

        // Authenticate the user
        $this->actingAs($user, 'sanctum');

        // Simulate an error in the service layer
        $this->mock(ArticleService::class, function ($mock) {
            $mock->shouldReceive('getSourcesByTopics')->andThrow(new \RuntimeException('Unable to fetch sources. Please try again later.'));
        });

        $response = $this->getJson('/api/preferences/sources?topics[]=Technology');

        $response->assertStatus(500)
                ->assertJsonFragment([
                    'message' => 'Failed to fetch sources. Please try again later.',
                ]);
    }

    /** @test */
    public function it_returns_400_if_topics_or_sources_are_not_arrays()
    {
        // Create a user
        $user = User::factory()->create();

        // Authenticate the user
        $this->actingAs($user, 'sanctum');

        // Topics as string
        $response = $this->getJson('/api/preferences/authors?topics=Technology&sources[]=TechCrunch');

        $response->assertStatus(400)
                ->assertJsonFragment([
                    'message' => 'The topics parameter must be an array.',
                ]);

        // Sources as string
        $response = $this->getJson('/api/preferences/authors?topics[]=Technology&sources=TechCrunch');

        $response->assertStatus(400)
                ->assertJsonFragment([
                    'message' => 'The sources parameter must be an array.',
                ]);
    }

    /** @test */
    public function it_returns_400_if_topics_or_sources_are_missing()
    {
        // Create a user
        $user = User::factory()->create();

        // Authenticate the user
        $this->actingAs($user, 'sanctum');

        // Missing topics
        $response = $this->getJson('/api/preferences/authors?sources[]=TechCrunch');

        $response->assertStatus(400)
                ->assertJsonFragment([
                    'message' => 'Topics and sources are required.',
                ]);

        // Missing sources
        $response = $this->getJson('/api/preferences/authors?topics[]=Technology');

        $response->assertStatus(400)
                ->assertJsonFragment([
                    'message' => 'Topics and sources are required.',
                ]);
    }
    
    /** @test */
    public function it_handles_unexpected_errors_when_fetching_authors()
    {
        // Create a user
        $user = User::factory()->create();

        // Authenticate the user
        $this->actingAs($user, 'sanctum');

        // Simulate an error in the service layer
        $this->mock(ArticleService::class, function ($mock) {
            $mock->shouldReceive('getAuthorsByTopicsAndSources')->andThrow(new \RuntimeException('Unable to fetch authors. Please try again later.'));
        });

        $response = $this->getJson('/api/preferences/authors?topics[]=Technology&sources[]=TechCrunch');

        $response->assertStatus(500)
                ->assertJsonFragment([
                    'message' => 'Failed to fetch authors. Please try again later.',
                ]);
    }
}