<?php

use Illuminate\Database\Schema\Builder;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTestTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @param  Builder $builder
     * @param  DatabaseManager $databaseManager
     * @return void
     */
    public function up(Builder $builder, DatabaseManager $databaseManager)
    {
        $builder->create('test', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
        });

        $databaseManager->table('test')->insert(['name' => 'PirateKing']);
    }

    /**
     * Reverse the migrations.
     *
     * @param  Builder $builder
     * @return void
     */
    public function down(Builder $builder)
    {
        $builder->dropIfExists('test');
    }
}
