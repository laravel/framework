<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Tests\Database\MigratorTestConnection;

return new class extends Migration
{
    /**
     * The database connection that should be used by the migration.
     *
     * @var \Illuminate\Tests\Database\MigratorTestConnection
     */
    protected $connection = MigratorTestConnection::Sqlite3;

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('flights', function (Blueprint $table) {
            $table->id();
            $table->string('name');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('flights');
    }
};
