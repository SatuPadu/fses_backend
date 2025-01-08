<?php
namespace Database\Factories\Modules\Articles\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Modules\Articles\Models\Article;

class ArticleFactory extends Factory
{
    protected $model = Article::class;

    public function definition()
    {
        $topicsAndSourcesAndAuthors = [
            'Technology' => [
                'sources' => ['TechCrunch', 'Wired'],
                'authors' => ['Alice Tech', 'Bob Innovator'],
            ],
            'Health' => [
                'sources' => ['Healthline', 'WebMD'],
                'authors' => ['Dr. Healthy', 'Nurse Care'],
            ],
            'Business' => [
                'sources' => ['Forbes', 'Bloomberg'],
                'authors' => ['John Market', 'Jane CEO'],
            ],
            'Science' => [
                'sources' => ['National Geographic', 'Scientific American'],
                'authors' => ['Dr. Discover', 'Prof. Science'],
            ],
        ];

        $topic = $this->faker->randomElement(array_keys($topicsAndSourcesAndAuthors));
        $source = $this->faker->randomElement($topicsAndSourcesAndAuthors[$topic]['sources']);
        $author = $this->faker->randomElement($topicsAndSourcesAndAuthors[$topic]['authors']);

        return [
            'title' => $this->faker->sentence,
            'description' => $this->faker->paragraph,
            'content' => $this->faker->paragraphs(3, true),
            'author' => $author,
            'source_name' => $source,
            'url' => $this->faker->url,
            'thumbnail' => $this->faker->imageUrl,
            'published_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'topic' => $topic,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}