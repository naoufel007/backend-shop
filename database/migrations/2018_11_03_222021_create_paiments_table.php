<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePaimentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('paiments', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('fournisseur_id');
            $table->unsignedInteger('user_id');
            $table->decimal('montant',11,2);
            $table->enum('type',['AVANCE','PAIMENT'])->default('PAIMENT');
            $table->timestamps();
            $table->foreign('user_id')->references('id')->on('users');
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
        Schema::dropIfExists('paiments');
    }
}
