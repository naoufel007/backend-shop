<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
class ClientSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('clients')->truncate();
		factory(App\Client::class, 150)->create();
		$association = [];
		for($a = 1; $a <= 4; $a++) {
			for($b = 1; $b <= 150; $b++) {
				$association[] = [
					"agence_id" => $a,
					"client_id" => $b,
					"created_at" => now(),
					"updated_at" => now()
				];
			}
		}
		DB::table('agence_client')->insert($association);
    }
}
