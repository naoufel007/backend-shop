<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->string('role')->default("user");// user, admin
            $table->unsignedInteger('agence_id');
            $table->foreign('agence_id')->references('id')->on('agences');
            $table->string('password');
            $table->string('addresse');
            $table->string('cin')->unique();
            $table->string('telephone')->unique();
            $table->decimal('p_casio_achat',4,2);
            $table->decimal('p_casio_vente',4,2);
            $table->decimal('p_service',4,2);
            $table->rememberToken();
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
        Schema::dropIfExists('users');
    }
}
