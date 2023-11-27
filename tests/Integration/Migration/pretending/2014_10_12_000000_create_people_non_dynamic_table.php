<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreatePeopleNonDynamicTable extends Migration
{
    public function up()
    {
        Schema::create('people', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
        });

        DB::table('people')->insert([
            ['email' => 'jane@example.com', 'name' => 'Jane Doe', 'password' => 'secret'],
            ['email' => 'john@example.com', 'name' => 'John Doe', 'password' => 'secret'],
        ]);
    }
}
