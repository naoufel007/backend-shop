<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAchatsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('achats', function (Blueprint $table) {
            $table->increments('id');
            $table->decimal('montant',11,2);
            $table->unsignedInteger("fournisseur_id");
            $table->unsignedInteger("user_id");
            $table->unsignedInteger("agence_id");
            $table->timestamps();
            $table->foreign('fournisseur_id')->references('id')->on('fournisseurs');
            $table->foreign('user_id')->references('id')->on('users');
            $table->foreign('agence_id')->references('id')->on('agences');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('achats');
    }
}
