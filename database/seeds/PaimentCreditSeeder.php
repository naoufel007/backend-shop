<?php

use Illuminate\Database\Seeder;

class PaimentCreditSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('paiment_credits')->truncate();
        factory(App\PaimentCredit::class, 86)->create();
    }
}
