<?php

namespace Ensi\LaravelAuditing\Tests\Data\Models\Factories;

use Ensi\LaravelAuditing\Tests\Data\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/*
|--------------------------------------------------------------------------
| User Factories
|--------------------------------------------------------------------------
|
*/

/**
 * @method User create(array $extra = [])
 */
class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition(): array
    {
        return [
            'is_admin' => $this->faker->randomElement([0, 1]),
            'first_name' => $this->faker->firstName,
            'last_name' => $this->faker->lastName,
            'email' => $this->faker->unique()->safeEmail,
            'password' => $this->faker->password(),
        ];
    }
}
