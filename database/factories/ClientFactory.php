<?php

use Faker\Generator as Faker;

$factory->define(App\Client::class, function (Faker $faker) {
    return [
        'nom' => $faker->name,
        'cin' => $faker->unique()->regexify('[A-Z]{1,2}[0-9]{6}'),
        'telephone' => $faker->phoneNumber,
        'points' => $faker->randomNumber(3),
        'credit' => $faker->randomNumber(3),
    ];
});
