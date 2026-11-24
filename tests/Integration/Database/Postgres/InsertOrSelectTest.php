<?php

namespace Illuminate\Tests\Integration\Database\Postgres;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\RequiresOperatingSystem;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;

#[RequiresPhpExtension('pdo_pgsql')]
#[RequiresOperatingSystem('Linux|Darwin')]
class InsertOrSelectTest extends PostgresTestCase
{
    protected function afterRefreshingDatabase()
    {
        Schema::create('insert_or_select_users', function (Blueprint $table) {
            $table->id();
            $table->string('email')->unique();
            $table->string('name');
        });
    }

    protected function destroyDatabaseMigrations()
    {
        Schema::drop('insert_or_select_users');
    }

    public function testInsertOrSelectReturnsInsertedAndExistingRecords()
    {
        if (version_compare(DB::getServerVersion(), '19', '<')) {
            $this->markTestSkipped('ON CONFLICT DO SELECT requires PostgreSQL 19 or newer.');
        }

        DB::table('insert_or_select_users')->insert(['email' => 'taylor@laravel.com', 'name' => 'Taylor']);

        $result = DB::table('insert_or_select_users')->insertOrSelect([
            ['email' => 'taylor@laravel.com', 'name' => 'Not Taylor'],
            ['email' => 'abigail@laravel.com', 'name' => 'Abigail'],
        ], 'email', ['email', 'name']);

        $this->assertEquals([
            'abigail@laravel.com' => 'Abigail',
            'taylor@laravel.com' => 'Taylor',
        ], $result->pluck('name', 'email')->all());

        $this->assertSame(2, DB::table('insert_or_select_users')->count());
    }
}
