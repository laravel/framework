<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DynamicContentNotShown extends Migration
{
    public function up()
    {
        Schema::create('blogs', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
        });

        DB::statement("ALTER TABLE pseudo_table_name MODIFY column_name VARCHAR(191);");

        DB::table('people')->get()->each(function ($table) {
            DB::table('blogs')->insert([
                'name' => "{$table->name} Blog",
            ]);
        });
    }
}
