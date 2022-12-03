<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateProduitsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('produits', function (Blueprint $table) {
            $table->increments('id');
            $table->string('code')->unique();
            $table->string('nom')->unique();
            $table->decimal('prix_achat',11,2);
            $table->decimal('prix_vente',11,2);
            $table->integer('fournisseur_id');
            $table->integer('agence_id');
            $table->integer('quantite');
            $table->integer('quantite_casio');
            $table->decimal('prix_achat_casio',11,2);
            $table->decimal('prix_vente_casio',11,2);
            $table->integer('points_g')->default(0);
            $table->integer('points_d')->default(0);
            $table->decimal('pourcentage_g',4,2);
            $table->decimal('pourcentage_d',4,2);
            $table->integer('max');
            $table->integer('min');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('produits');
    }
}
