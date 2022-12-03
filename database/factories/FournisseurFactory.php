<?php

use Faker\Generator as Faker;

$factory->define(App\Fournisseur::class, function (Faker $faker) {
    return [
        'name' => $faker->firstName." ".$faker->lastName,
        'telephone' => $faker->phoneNumber,
        'fax' => $faker->phoneNumber,
        'adresse' => $faker->address

    ];
});
 