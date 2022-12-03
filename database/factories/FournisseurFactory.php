<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Fournisseur>
 */
class FournisseurFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'name' => fake()->firstName()." ".fake()->lastName(),
            'telephone' => fake()->phoneNumber(),
            'fax' => fake()->phoneNumber(),
            'adresse' => fake()->address()
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



 