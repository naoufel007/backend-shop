<?php

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        $this->call(AgenceSeeder::class);
        $this->call(UsersSeeder::class);
         $this->call(FournisseurSeeder::class);
         $this->call(ProduitSeeder::class);
         $this->call(ClientSeeder::class);
         $this->call(PaimentFournisserSeeder::class);
         //$this->call(CreditSeeder::class);
         //$this->call(PaimentCreditSeeder::class);
         DB::statement('SET FOREIGN_KEY_CHECKS=1;');

    }
}
