<?php

use Faker\Generator as Faker;

$factory->define(App\PaimentCredit::class, function (Faker $faker) {
    return [
        'user_id' => $faker->numberBetween($min = 1, $max = 50),
        'credit_id' => $faker->numberBetween($min = 1, $max = 100),
        'montant' => $faker->randomNumber(3)
    ];
});
 