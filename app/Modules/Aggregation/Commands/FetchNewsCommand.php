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
        $topics = $this->topicService->getTopics();

        if (empty($topics)) {
            $this->warn('No topics found. Fetching from The Guardian API...');
            $this->topicService->fetchAndStoreTopics();
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
                if (Cache::get("api_failed_{$key}") || $this->exceedsLimit($key, $limit)) {
                    $this->warn("Skipping {$key} due to rate limits or failure.");
                    continue;
                }

                try {
                    $apiInstance = app($apiClass);
                    $articles = $apiInstance->fetchArticles($batch);

                    foreach ($articles as $articleData) {
                        $this->articleService->processAndStoreArticle($articleData);
                    }

                    $this->incrementUsage($key);

                } catch (\Exception $e) {
                    $this->error("Failed to fetch articles from {$key}: " . $e->getMessage());
                    Cache::put("api_failed_{$key}", true, now()->addDay());
                }
            }
        }

        $this->info('All topics processed successfully.');
    }

    protected function exceedsLimit(string $api, int $limit): bool
    {
        return Cache::get("api_usage_{$api}", 0) >= $limit;
    }

    protected function incrementUsage(string $api): void
    {
        Cache::increment("api_usage_{$api}");
        Cache::put("api_usage_reset_{$api}", now()->addHours(12), now()->addHours(12));
    }
}