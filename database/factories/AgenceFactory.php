<?php

use Faker\Generator as Faker;

$factory->define(App\Agence::class, function (Faker $faker) {
    return [
        'nom' => $faker->unique()->regexify('Agence [0-9]'),
        'addresse' => $faker->unique()->streetAddress
    ];
});
