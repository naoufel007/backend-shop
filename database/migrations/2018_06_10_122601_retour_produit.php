<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class RetourProduit extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('retour_produit', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('retour_id');            
            $table->unsignedInteger('produit_id');
            $table->integer('quantite_vente_produit');
            $table->decimal('prix_vente_recommande',11,2);
            $table->decimal('prix_vente',11,2);
            $table->decimal('remise');
            $table->string('type'); //casio ou nouveau
            $table->timestamps();
            $table->foreign('retour_id')->references('id')->on('retours');
            $table->foreign('produit_id')->references('id')->on('produits');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('retour_produit');
    }
}
