<?php

use Faker\Generator as Faker;

/*
|--------------------------------------------------------------------------
| Model Factories
|--------------------------------------------------------------------------
|
| This directory should contain each of the model factory definitions for
| your application. Factories provide a convenient way to generate new
| model instances for testing / seeding your application's database.
|
*/

$factory->define(App\User::class, function (Faker $faker) {
    static $password;

    return [
        'name' => $faker->name,
        'password' => $password ?: $password = bcrypt('test@shop'),
        'pass' => 'test@shop',
        'remember_token' => str_random(10),
        'agence_id' => $faker->numberBetween($min = 1, $max = 4),
        'addresse' => $faker->streetAddress,
        'cin' => $faker->unique()->regexify('[A-Z]{1,2}[0-9]{6}'),
        'telephone' => $faker->unique()->phoneNumber,
        'p_casio_achat' => $faker->numberBetween($min = 1, $max = 4),
        'p_casio_vente' => $faker->numberBetween($min = 1, $max = 4),
        'p_service' => $faker->numberBetween($min = 1, $max = 4),
    ];
});
