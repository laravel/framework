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
        Schema::create('foo', function (Blueprint $table) {
            $table->increments('id');
            $table->string('foo');
        });

        Schema::connection('second_connection')->create('bar', function (Blueprint $table) {
            $table->increments('id');
            $table->string('bar');
        });

        Schema::connection('second_connection')->create('migrations', function (Blueprint $table) {
            $table->increments('id');
            $table->string('migration');
            $table->integer('batch');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('foo');
        Schema::connection('second_connection')->drop('bar');
        Schema::connection('second_connection')->drop('migrations');
    }
}
