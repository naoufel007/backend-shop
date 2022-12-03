<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Produit>
 */
class ProduitFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'code' => fake()->unique()->numerify('Code ###'),
            'nom' => fake()->unique()->numerify('Produit ###'),
            'prix_achat' => fake()->numberBetween($min = 10, $max = 30),
            'prix_vente' => fake()->numberBetween($min = 30, $max = 100),
            'agence_id' => fake()->numberBetween($min = 1, $max = 4),
            'quantite' => fake()->randomNumber(3),
            'quantite_casio' => fake()->randomNumber(2),
            'prix_achat_casio' => fake()->numberBetween($min = 10, $max = 30),
            'prix_vente_casio' => fake()->numberBetween($min = 10, $max = 30),
            'pourcentage_g' => fake()->randomNumber(1),
            'pourcentage_d' => fake()->randomNumber(1),
            'max' => fake()->randomNumber(3),
            'min' => fake()->randomNumber(1),
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



 

 
