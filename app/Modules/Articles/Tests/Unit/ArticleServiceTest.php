<?php

namespace Tests\Unit;

use Tests\TestCase;
use Mockery;
use App\Modules\Articles\Services\ArticleService;
use App\Modules\Articles\Repositories\ArticleRepository;

class ArticleServiceTest extends TestCase
{
    /**
     * @var \Mockery\LegacyMockInterface&\App\Modules\Articles\Repositories\ArticleRepository
     */
    protected $articleRepo;

    /**
     * @var \App\Modules\Articles\Services\ArticleService
     */
    protected $articleService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->articleRepo = Mockery::mock(ArticleRepository::class);
        $this->articleService = new ArticleService($this->articleRepo);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function it_fetches_article_by_valid_id()
    {
        $articleId = 1;
        $mockArticle = ['id' => $articleId, 'title' => 'Sample Article'];

        $this->articleRepo
            ->shouldReceive('getArticleById')
            ->with($articleId)
            ->once()
            ->andReturn($mockArticle);

        $result = $this->articleService->getArticleById($articleId);

        $this->assertEquals($mockArticle, $result);
    }

    /** @test */
    public function it_throws_exception_when_article_not_found()
    {
        $articleId = 999;

        $this->articleRepo
            ->shouldReceive('getArticleById')
            ->with($articleId)
            ->once()
            ->andThrow(new \RuntimeException('Unable to fetch article details. Please try again later.'));

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Unable to fetch article details. Please try again later.');

        $this->articleService->getArticleById($articleId);
    }

    /** @test */
    public function it_fetches_sources_by_topics()
    {
        $topics = ['Technology', 'Health'];
        $mockSources = ['Source 1', 'Source 2'];

        $this->articleRepo
            ->shouldReceive('fetchSourcesByTopics')
            ->with($topics)
            ->once()
            ->andReturn($mockSources);

        $result = $this->articleService->getSourcesByTopics($topics);

        $this->assertEquals($mockSources, $result);
    }

    /** @test */
    public function it_throws_exception_for_empty_topics()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Topics cannot be empty.');

        // Call the method with an empty array
        $this->articleService->getSourcesByTopics([]);
    }

    /** @test */
    public function it_fetches_authors_by_topics_and_sources()
    {
        $topics = ['Technology', 'Health'];
        $sources = ['TechCrunch', 'Healthline'];
        $mockAuthors = ['Alice Tech', 'Dr. Healthy'];


        $this->articleRepo
            ->shouldReceive('fetchAuthorsByTopicsAndSources')
            ->with($topics, $sources)
            ->once()
            ->andReturn($mockAuthors);

        $result = $this->articleService->getAuthorsByTopicsAndSources($topics, $sources);

        $this->assertEquals($mockAuthors, $result);
    }

    /** @test */
    public function it_throws_exception_when_sources_are_empty()
    {
        $topics = ['Technology'];
        $sources = [];

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Sources cannot be empty.');

        $this->articleService->getAuthorsByTopicsAndSources($topics, $sources);
    }

    /** @test */
    public function it_handles_empty_authors_by_topics_and_sources()
    {
        $topics = ['Technology'];
        $sources = ['TechCrunch'];
        $mockAuthors = [];

        $this->articleRepo
            ->shouldReceive('fetchAuthorsByTopicsAndSources')
            ->with($topics, $sources)
            ->once()
            ->andReturn($mockAuthors);

        $result = $this->articleService->getAuthorsByTopicsAndSources($topics, $sources);

        $this->assertEquals([], $result);
    }

    /** @test */
    public function it_throws_exception_when_fetching_authors_fails()
    {
        $topics = ['Technology'];
        $sources = ['Source 1'];

        $this->articleRepo
            ->shouldReceive('fetchAuthorsByTopicsAndSources')
            ->with($topics, $sources)
            ->once()
            ->andThrow(new \RuntimeException('Unable to fetch authors. Please try again later.'));

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Unable to fetch authors. Please try again later.');

        $this->articleService->getAuthorsByTopicsAndSources($topics, $sources);
    }
}