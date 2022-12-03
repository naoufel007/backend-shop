<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Paiment>
 */
class FournisseurPaimentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'fournisseur_id' => fake()->numberBetween($min = 1, $max = 150),
            'user_id' => fake()->numberBetween($min = 1, $max = 50),
            'montant' => fake()->randomNumber(3),
            'type' => fake()->randomElement(['AVANCE','PAIMENT'])
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



 
