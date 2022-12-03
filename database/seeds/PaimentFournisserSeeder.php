<?php

use Illuminate\Database\Seeder;

class PaimentFournisserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('paiments')->truncate();
        factory(App\Paiment::class, 200)->create();
    }
}
