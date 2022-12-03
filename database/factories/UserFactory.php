<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\User>
 */
class UserFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'name' => fake()->name(),
            'password' => bcrypt('test@shop'),
            'pass' => 'test@shop',
            'remember_token' => Str::random(10),
            'agence_id' => fake()->numberBetween($min = 1, $max = 4),
            'addresse' => fake()->streetAddress(),
            'cin' => fake()->unique()->regexify('[A-Z]{1,2}[0-9]{6}'),
            'telephone' => fake()->unique()->phoneNumber(),
            'p_casio_achat' => fake()->numberBetween($min = 1, $max = 4),
            'p_casio_vente' => fake()->numberBetween($min = 1, $max = 4),
            'p_service' => fake()->numberBetween($min = 1, $max = 4),
        ];
    }

    /*
    /**
     * Indicate that the model's email address should be unverified.
     *
     * @return static  
    public function unverified()
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
    */
}
