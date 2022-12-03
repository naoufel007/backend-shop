<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCommissionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('commissions', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('vente_id')->nullable();
            $table->unsignedInteger('service_id')->nullable();    
            $table->unsignedInteger('retour_id')->nullable();
            $table->unsignedInteger('user_id');
            $table->unsignedInteger('produit_id')->nullable();
            $table->integer('quantite')->nullable();
            $table->decimal('commission',11,2);
            $table->timestamps();
            $table->foreign('vente_id')->references('id')->on('ventes')->onDelete('cascade');
            $table->foreign('retour_id')->references('id')->on('retours');
            $table->foreign('user_id')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('commissions');
    }
}
