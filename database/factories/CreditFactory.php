<?php

use Faker\Generator as Faker;

$factory->define(App\Credit::class, function (Faker $faker) {
    return [
        'user_id' => $faker->numberBetween($min = 1, $max = 50),
        'client_id' => $faker->numberBetween($min = 1, $max = 150),
        'agence_id' => $faker->numberBetween($min = 1, $max = 4),
        'montant' => $faker->randomNumber(3),
    ];
});
 