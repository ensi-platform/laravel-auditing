<?php

namespace Ensi\LaravelAuditing\Tests\Data\Models\Factories;

use Ensi\LaravelAuditing\Tests\Data\Models\ApiModel;
use Illuminate\Database\Eloquent\Factories\Factory;
use Ramsey\Uuid\Uuid;

/*
|--------------------------------------------------------------------------
| APIModel Factory
|--------------------------------------------------------------------------
|
*/
class ApiModelFactory extends Factory
{
    protected $model = ApiModel::class;

    public function definition(): array
    {
        return [
            'api_model_id' => Uuid::uuid4(),
            'content' => $this->faker->unique()->paragraph(6),
            'published_at' => null,
        ];
    }
}
