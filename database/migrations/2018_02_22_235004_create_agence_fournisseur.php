<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAgenceFournisseur extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('agence_fournisseur', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('agence_id');
            $table->unsignedInteger('fournisseur_id');
            $table->foreign('agence_id')->references('id')->on('agences');
            $table->foreign('fournisseur_id')->references('id')->on('fournisseurs');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('agence_fournisseur');
    }
}
