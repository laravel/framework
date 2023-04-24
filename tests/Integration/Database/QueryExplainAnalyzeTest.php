<?php

namespace Illuminate\Tests\Integration\Database;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class QueryExplainAnalyzeTest extends DatabaseTestCase
{
    public function testExplainAnalyzeMySql()
    {
        if ($this->driver !== 'mysql') {
            $this->markTestSkipped('Test requires a MySQL connection.');
        }

        Schema::create('users', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
        });

        $this->assertObjectHasProperty('EXPLAIN', DB::table('users')->explainAnalyze()?->first());

        Schema::drop('users');
    }

    public function testExplainAnalyzePostgres()
    {
        if ($this->driver !== 'pgsql') {
            $this->markTestSkipped('Test requires a PgSQl connection.');
        }

        Schema::create('users', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
        });

        $this->assertObjectHasProperty('QUERY PLAN', DB::table('users')->explainAnalyze()?->first());

        Schema::drop('users');
    }
}
