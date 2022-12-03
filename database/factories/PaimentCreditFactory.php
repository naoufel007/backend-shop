<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\PaimentCredit>
 */
class PaimentCreditFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'user_id' => fake()->numberBetween($min = 1, $max = 50),
            'credit_id' => fake()->numberBetween($min = 1, $max = 100),
            'montant' => fake()->randomNumber(3)
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



 

 