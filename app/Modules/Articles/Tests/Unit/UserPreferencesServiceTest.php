<?php

namespace App\Modules\Articles\Tests\Unit;

use Tests\TestCase;
use Mockery;
use App\Modules\Articles\Services\UserPreferencesService;
use App\Modules\Articles\Repositories\UserPreferencesRepository;

class UserPreferencesServiceTest extends TestCase
{
    /**
     * @var \Mockery\LegacyMockInterface&\App\Modules\Articles\Repositories\UserPreferencesRepository
     */
    protected $preferencesRepo;

    /**
     * @var \App\Modules\Articles\Services\UserPreferencesService
     */
    protected $preferencesService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->preferencesRepo = Mockery::mock(UserPreferencesRepository::class);
        $this->preferencesService = new UserPreferencesService($this->preferencesRepo);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function it_saves_valid_preferences()
    {
        $userId = 1;
        $preferences = [
            'topics' => ['Technology', 'Health'],
            'sources' => ['Source 1', 'Source 2'],
            'authors' => ['Author A', 'Author B'],
        ];

        // Mock the repository methods for validation
        $this->preferencesRepo
            ->shouldReceive('getValidTopics')
            ->once()
            ->andReturn(['Technology', 'Health', 'Science']);
        $this->preferencesRepo
            ->shouldReceive('getValidSources')
            ->once()
            ->andReturn(['Source 1', 'Source 2', 'Source 3']);
        $this->preferencesRepo
            ->shouldReceive('getValidAuthors')
            ->once()
            ->andReturn(['Author A', 'Author B', 'Author C']);

        // Mock the repository behavior for saving preferences
        $this->preferencesRepo
            ->shouldReceive('savePreferences')
            ->once()
            ->with($userId, $preferences)
            ->andReturnTrue();

        // Execute the service method
        $this->preferencesService->setPreferences($userId, $preferences);

        // Assert no exceptions were thrown
        $this->assertTrue(true, 'Preferences were validated and saved successfully.');
    }

    /** @test */
    public function it_throws_error_for_invalid_preferences()
    {
        $userId = 1;
        $preferences = [
            'topics' => ['Invalid Topic'],
        ];

        // Mock the repository methods to provide valid data for comparison
        $this->preferencesRepo
            ->shouldReceive('getValidTopics')
            ->once()
            ->andReturn(['Technology', 'Health', 'Science']); // Does not include 'Invalid Topic'
        $this->preferencesRepo
            ->shouldReceive('getValidSources')
            ->once()
            ->andReturn(['Source 1', 'Source 2', 'Source 3']);
        $this->preferencesRepo
            ->shouldReceive('getValidAuthors')
            ->once()
            ->andReturn(['Author A', 'Author B', 'Author C']);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid topic: Invalid Topic');

        // Execute the service method, which triggers validation
        $this->preferencesService->setPreferences($userId, $preferences);
    }

    /** @test */
    public function it_handles_empty_preferences_gracefully()
    {
        $userId = 1;
        $preferences = []; // Empty preferences

        // Mock the repository behavior for saving preferences
        $this->preferencesRepo
            ->shouldReceive('savePreferences')
            ->once()
            ->with($userId, $preferences)
            ->andReturnTrue();

        // Execute the service method
        $this->preferencesService->setPreferences($userId, $preferences);

        // Assert no exceptions were thrown
        $this->assertTrue(true, 'Empty preferences were handled gracefully.');
    }

    /** @test */
    public function it_retrieves_user_preferences()
    {
        $userId = 1;
        $mockPreferences = [
            'topics' => ['Technology'],
            'sources' => ['Source 1'],
            'authors' => ['Author A'],
        ];

        $this->preferencesRepo
            ->shouldReceive('getPreferences')
            ->with($userId)
            ->once()
            ->andReturn($mockPreferences);

        $result = $this->preferencesService->getPreferences($userId);

        $this->assertEquals($mockPreferences, $result);
    }

    /** @test */
    public function it_returns_empty_preferences_if_none_exist()
    {
        $userId = 1;

        $this->preferencesRepo
            ->shouldReceive('getPreferences')
            ->with($userId)
            ->once()
            ->andReturn([]);

        $result = $this->preferencesService->getPreferences($userId);

        $this->assertEquals([], $result);
    }

    /** @test */
    public function it_throws_exception_when_retrieving_preferences_fails()
    {
        $userId = 1;

        $this->preferencesRepo
            ->shouldReceive('getPreferences')
            ->with($userId)
            ->once()
            ->andThrow(new \RuntimeException('Failed to fetch preferences.'));

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Failed to fetch preferences.');

        $this->preferencesService->getPreferences($userId);
    }
}