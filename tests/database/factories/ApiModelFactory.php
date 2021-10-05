<?php
namespace Ensi\LaravelAuditing\Database\Factories;

use Ensi\LaravelAuditing\Tests\Models\ApiModel;
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
            'content'      => $this->faker->unique()->paragraph(6),
            'published_at' => null,
        ];
    }

}
