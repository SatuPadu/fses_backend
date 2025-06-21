<?php

namespace App\Modules\Aggregation\Commands;

use Illuminate\Console\Command;
use App\Modules\Aggregation\Services\NewsApiService;
use App\Modules\Aggregation\Services\GuardianService;
use App\Modules\Aggregation\Services\NYTimesService;
use App\Modules\Articles\Services\ArticleService;
use App\Modules\Articles\Services\TopicService;
use Illuminate\Support\Facades\Cache;

class FetchNewsCommand extends Command
{
    protected $signature = 'news:fetch';
    protected $description = 'Fetch news articles from various sources while respecting API limits';

    protected const NEWS_API_LIMIT = 24;      // Max 24 calls per 12 hours
    protected const GUARDIAN_LIMIT = 50;     // Max 50 calls per 12 hours
    protected const NYTIMES_LIMIT = 50;      // Max 50 calls per 12 hours

    protected ArticleService $articleService;
    protected TopicService $topicService;

    public function __construct(ArticleService $articleService, TopicService $topicService)
    {
        parent::__construct();
        $this->articleService = $articleService;
        $this->topicService = $topicService;
    }

    public function handle()
    {
        // Fetch topics directly
        $topics = $this->topicService->getTopics(true);

        if (empty($topics)) {
            $this->warn('No topics found. Fetching from The Guardian API...');
            
            // Fetch and store topics
            $this->topicService->fetchAndStoreTopics();

            // Re-fetch topics after storing
            $topics = $this->topicService->getTopics();

            if (empty($topics)) {
                $this->error('Failed to fetch topics.');
                return;
            }
        }

        $topicsPerBatch = ceil(count($topics) / 12);
        $batches = array_chunk($topics, $topicsPerBatch);

        $apis = [
            'newsApi' => [NewsApiService::class, self::NEWS_API_LIMIT],
            'guardianApi' => [GuardianService::class, self::GUARDIAN_LIMIT],
            'nyTimesApi' => [NYTimesService::class, self::NYTIMES_LIMIT],
        ];

        foreach ($batches as $batch) {
            $this->info('Processing topics: ' . implode(', ', $batch));

            foreach ($apis as $key => [$apiClass, $limit]) {
                $cacheKey = "news:fetch:api:{$key}";

                if ($this->exceedsLimit($cacheKey, $limit)) {
                    $this->warn("Skipping {$key} due to rate limits.");
                    continue;
                }

                try {
                    $apiInstance = app($apiClass);
                    $articles = $apiInstance->fetchArticles($batch);

                    foreach ($articles as $articleData) {
                        $this->articleService->processAndStoreArticle($articleData);
                    }

                    $this->incrementUsage($cacheKey);

                } catch (\Exception $e) {
                    $this->error("Failed to fetch articles from {$key}: " . $e->getMessage());
                    Cache::put("news:fetch:api_failed:{$key}", true, now()->addDay());
                }
            }
        }

        $this->info('All topics processed successfully.');
    }

    protected function exceedsLimit(string $cacheKey, int $limit): bool
    {
        return Cache::get("{$cacheKey}:usage", 0) >= $limit;
    }

    protected function incrementUsage(string $cacheKey): void
    {
        Cache::increment("{$cacheKey}:usage");
        Cache::put("{$cacheKey}:reset", now()->addHours(12), now()->addHours(12));
    }
}