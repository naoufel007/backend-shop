<?php

use Faker\Generator as Faker;

$factory->define(App\Produit::class, function (Faker $faker) {
    return [
        'code' => $faker->unique()->numerify('Code ###'),
        'nom' => $faker->unique()->numerify('Produit ###'),
        'prix_achat' => $faker->numberBetween($min = 10, $max = 30),
        'prix_vente' => $faker->numberBetween($min = 30, $max = 100),
        'agence_id' => $faker->numberBetween($min = 1, $max = 4),
        'quantite' => $faker->randomNumber(3),
        'quantite_casio' => $faker->randomNumber(2),
        'prix_achat_casio' => $faker->numberBetween($min = 10, $max = 30),
        'prix_vente_casio' => $faker->numberBetween($min = 10, $max = 30),
        'pourcentage_g' => $faker->randomNumber(1),
        'pourcentage_d' => $faker->randomNumber(1),
        'max' => $faker->randomNumber(3),
        'min' => $faker->randomNumber(1),
    ];
});
