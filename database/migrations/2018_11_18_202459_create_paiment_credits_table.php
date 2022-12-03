<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePaimentCreditsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('paiment_credits', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('credit_id');
            $table->unsignedInteger('user_id');
            $table->decimal('montant');
            $table->timestamps();
            $table->foreign('user_id')->references('id')->on('users');
            $table->foreign('credit_id')->references('id')->on('credits');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('paiment_credits');
    }
}
