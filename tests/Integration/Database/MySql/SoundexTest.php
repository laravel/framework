<?php

namespace Illuminate\Tests\Integration\Database\MySql;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SoundexTest extends MySqlTestCase
{
    protected function defineDatabaseMigrationsAfterDatabaseRefreshed()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('email', 200);
            $table->string('first_name');
            $table->string('last_name');
        });
    }

    protected function destroyDatabaseMigrations()
    {
        Schema::drop('users');
    }

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('users')->insert([
            ['email' => 'taylor@laravel.com', 'first_name' => 'Taylor', 'last_name' => 'Otwell'],
            ['email' => 'tailor@laravel.com', 'first_name' => 'Tailor', 'last_name' => 'Otwel'],
            ['email' => 'jason@sloff.com', 'first_name' => 'Json', 'last_name' => 'Slooff']
        ]);
    }

    public function testWhereSoundex()
    {
        $users = DB::table('users')->whereSoundex('first_name', 'tailor')->get();

        $this->assertCount(2, $users);
        $this->assertEquals('Taylor', $users[0]->first_name);
        $this->assertEquals('Tailor', $users[1]->first_name);

        $users = DB::table('users')->whereSoundex('last_name', 'Otuel')->get();

        $this->assertCount(2, $users);
        $this->assertEquals('Otwell', $users[0]->last_name);
        $this->assertEquals('Otwel', $users[1]->last_name);

        $users = DB::table('users')->whereSoundex('last_name', 'sluf')->get();

        $this->assertCount(1, $users);
        $this->assertEquals('Slooff', $users[0]->last_name);
    }
}
