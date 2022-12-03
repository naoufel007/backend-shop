<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
class FournisseurSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('fournisseurs')->truncate();
        \App\Fournisseur::factory(150)->create();
        $association = [];
		for($a = 1; $a <= 4; $a++) {
			for($b = 1; $b <= 150; $b++) {
				$association[] = [
                    "agence_id" => $a,
                    "fournisseur_id" => $b
                ];
			}
		}
		DB::table('agence_fournisseur')->insert($association);
    }
}
