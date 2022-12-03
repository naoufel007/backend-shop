<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateRetoursTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('retours', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('agence_id');
            $table->unsignedInteger('client_id');
            $table->unsignedInteger('user_id');
            $table->decimal('montant',11,2);
            $table->integer('type_vente'); // retour d'une vente type_retour?
            $table->timestamps();
            $table->foreign('agence_id')->references('id')->on('agences');
            $table->foreign('client_id')->references('id')->on('clients');
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
        Schema::dropIfExists('retours');
    }
}
