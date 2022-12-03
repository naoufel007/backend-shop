<?php

use Faker\Generator as Faker;

$factory->define(App\Paiment::class, function (Faker $faker) {
    return [
        'fournisseur_id' => $faker->numberBetween($min = 1, $max = 150),
        'user_id' => $faker->numberBetween($min = 1, $max = 50),
        'montant' => $faker->randomNumber(3),
        'type' => $faker->randomElement(['AVANCE','PAIMENT'])
    ];
});
