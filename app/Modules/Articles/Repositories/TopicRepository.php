<?php

namespace App\Modules\Articles\Repositories;

use App\Modules\Articles\Models\Topic;

class TopicRepository
{
    /**
     * Insert topics into the database.
     *
     * @param array $topics
     * @return void
     */
    public function insertTopics(array $topics): void
    {
        if (!empty($topics)) {
            Topic::insert($topics);
        }
    }

    /**
     * Retrieve all topic names.
     *
     * @return array
     */
    public function getAllTopicNames(): array
    {
        return Topic::pluck('name')->toArray();
    }
}