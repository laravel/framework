<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFooBarTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('first_connection')->create('foo', function (Blueprint $table) {
            $table->increments('id');
            $table->string('foo');
        });

        Schema::connection('second_connection')->create('bar', function (Blueprint $table) {
            $table->increments('id');
            $table->string('bar');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('first_connection')->drop('foo');
        Schema::connection('second_connection')->drop('bar');
    }
}
