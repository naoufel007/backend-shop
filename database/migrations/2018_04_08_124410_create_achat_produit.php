<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAchatProduit extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('achat_produit', function (Blueprint $table) {
        $table->increments('id');
        $table->unsignedInteger('achat_id');            
        $table->unsignedInteger('produit_id');
        $table->integer('quantite_achat_produit');
        $table->decimal('prix_achat',11,2);
        $table->decimal('remise',4,2);
        $table->string('type'); //casio ou nouveau
        $table->integer('quantite_restante');
        $table->timestamps();
        $table->foreign('achat_id')->references('id')->on('achats');
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
        Schema::dropIfExists('achat_produit');
    }
}
