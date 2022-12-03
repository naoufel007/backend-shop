<?php

use Illuminate\Database\Seeder;

class CreditSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('credits')->truncate();
        factory(App\Credit::class, 200)->create();
    }
}
