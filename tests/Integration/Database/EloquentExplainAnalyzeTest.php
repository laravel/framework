<?php

namespace Illuminate\Tests\Integration\Database;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class EloquentExplainAnalyzeTest extends DatabaseTestCase
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

        $this->assertObjectHasProperty('EXPLAIN', UserExplainAnalyzeTest::query()->explainAnalyze()?->first());

        Schema::drop('users');
    }

    public function testExplainAnalyzePostgres()
    {
        if ($this->driver !== 'pgsql') {
            $this->markTestSkipped('Test requires a PgSQL connection.');
        }

        Schema::create('users', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
        });

        $this->assertObjectHasProperty('QUERY PLAN', UserExplainAnalyzeTest::query()->explainAnalyze()?->first());

        Schema::drop('users');
    }
}

class UserExplainAnalyzeTest extends Model
{
    protected $table = 'users';
    protected $fillable = ['name'];
}
