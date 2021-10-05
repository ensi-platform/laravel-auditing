<?php
namespace Ensi\LaravelAuditing\Database\Factories;

use Ensi\LaravelAuditing\Models\Audit;
use Ensi\LaravelAuditing\Tests\Models\Article;
use Ensi\LaravelAuditing\Tests\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/*
|--------------------------------------------------------------------------
| Audit Factory
|--------------------------------------------------------------------------
|
*/
class AuditFactory extends Factory
{
    protected $model = Audit::class;

    public function definition(): array
    {
        return [
            'subject_id' => function () {
                return User::factory()->create()->id;
            },
            'subject_type'    => User::class,
            'event'        => 'updated',
            'auditable_id' => function () {
                return Article::factory()->create()->id;
            },
            'auditable_type' => Article::class,
            'old_values'     => [],
            'new_values'     => [],
            'url'            => $this->faker->url,
            'ip_address'     => $this->faker->ipv4,
            'user_agent'     => $this->faker->userAgent,
            'tags'           => implode(',', $this->faker->words(4)),
        ];
    }

}
