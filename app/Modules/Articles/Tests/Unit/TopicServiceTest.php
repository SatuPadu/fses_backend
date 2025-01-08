<?php

namespace App\Modules\Articles\Tests\Unit;

use Tests\TestCase;
use Mockery;
use Illuminate\Support\Facades\Http;
use App\Modules\Articles\Services\TopicService;
use App\Modules\Articles\Repositories\TopicRepository;

class TopicServiceTest extends TestCase
{
    /**
     * @var \Mockery\LegacyMockInterface&\App\Modules\Articles\Repositories\TopicRepository
     */
    protected $topicRepo;

    /**
     * @var \App\Modules\Articles\Services\TopicService
     */
    protected $topicService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->topicRepo = Mockery::mock(TopicRepository::class);
        $this->topicService = new TopicService($this->topicRepo);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function it_fetches_all_topics_from_repository()
    {
        $mockTopics = ['Technology', 'Health'];

        $this->topicRepo
            ->shouldReceive('getAllTopicNames')
            ->once()
            ->andReturn($mockTopics);

        $result = $this->topicService->getTopics();

        $this->assertEquals($mockTopics, $result);
    }

    /** @test */
    public function it_returns_empty_array_when_no_topics_exist_in_repository()
    {
        $this->topicRepo
            ->shouldReceive('getAllTopicNames')
            ->once()
            ->andReturn([]);

        $result = $this->topicService->getTopics();

        $this->assertEquals([], $result);
    }

    /** @test */
    public function it_fetches_topics_from_api()
    {
        $mockApiResponse = [
            'response' => [
                'results' => [
                    ['id' => 1, 'webTitle' => 'Technology'],
                    ['id' => 2, 'webTitle' => 'Health'],
                ],
            ],
        ];

        Http::fake([
            '*' => Http::response($mockApiResponse, 200),
        ]);

        $service = Mockery::mock(get_class($this->topicService) . '[fetchTopicsFromApi]', [$this->topicRepo]);
        $service->shouldAllowMockingProtectedMethods();

        $result = $this->invokeMethod($this->topicService, 'fetchTopicsFromApi');

        $this->assertEquals($mockApiResponse['response']['results'], $result);
    }

    /** @test */
    public function it_returns_empty_array_when_no_topics_returned_from_api()
    {
        $mockApiResponse = [
            'response' => [
                'results' => [],
            ],
        ];

        Http::fake([
            '*' => Http::response($mockApiResponse, 200),
        ]);

        $result = $this->invokeMethod($this->topicService, 'fetchTopicsFromApi');

        $this->assertEquals([], $result);
    }

    /** @test */
    public function it_handles_api_errors_when_fetching_topics()
    {
        Http::fake([
            '*' => Http::response(null, 500),
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Failed to fetch topics from The Guardian API. Response status: 500');

        $this->invokeMethod($this->topicService, 'fetchTopicsFromApi');
    }

    /** @test */
    public function it_handles_runtime_exceptions_when_fetching_topics_from_repository()
    {
        $this->topicRepo
            ->shouldReceive('getAllTopicNames')
            ->once()
            ->andThrow(new \RuntimeException('Runtime error'));

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Runtime error');

        $this->topicService->getTopics();
    }

    /** @test */
    public function it_handles_general_exceptions_when_fetching_topics_from_repository()
    {
        $this->topicRepo
            ->shouldReceive('getAllTopicNames')
            ->once()
            ->andThrow(new \Exception('Unexpected error'));

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Unexpected error');

        $this->topicService->getTopics();
    }

    /**
     * Helper method to invoke private or protected methods.
     *
     * @param object $object
     * @param string $methodName
     * @param array $parameters
     * @return mixed
     */
    protected function invokeMethod($object, $methodName, array $parameters = [])
    {
        $reflection = new \ReflectionClass($object);
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs($object, $parameters);
    }
}