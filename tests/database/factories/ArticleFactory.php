<?php
namespace Greensight\LaravelAuditing\Database\Factories;

use Greensight\LaravelAuditing\Tests\Models\Article;
use Illuminate\Database\Eloquent\Factories\Factory;

/*
|--------------------------------------------------------------------------
| Article Factory
|--------------------------------------------------------------------------
|
*/
class ArticleFactory extends Factory
{
    protected $model = Article::class;

    public function definition(): array
    {
        return [
            'title'        => $this->faker->unique()->sentence,
            'content'      => $this->faker->unique()->paragraph(6),
            'published_at' => null,
            'reviewed'     => $this->faker->randomElement([0, 1]),
        ];
    }

}
