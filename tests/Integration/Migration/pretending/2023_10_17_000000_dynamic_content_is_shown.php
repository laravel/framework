<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DynamicContentIsShown extends Migration
{
    public function up()
    {
        Schema::create('blogs', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
        });

        /** @var \Illuminate\Support\Collection $tablesList */
        $tablesList = DB::ignorePretendModeForCallback(function () {
            return DB::table('people')->get();
        });

        DB::statement("ALTER TABLE pseudo_table_name MODIFY column_name VARCHAR(191);");

        $tablesList->each(function ($table) {
            DB::table('blogs')->insert([
                'name' => "{$table->name} Blog",
            ]);
        });
    }
}
