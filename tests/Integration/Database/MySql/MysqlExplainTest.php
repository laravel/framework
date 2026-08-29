<?php

namespace Illuminate\Tests\Integration\Database\MySql;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MysqlExplainTest extends MySqlTestCase
{
    protected function afterRefreshingDatabase()
    {
        Schema::create('mysql_explain', function (Blueprint $table) {
            $table->id();
            $table->string('name');
        });
    }

    protected function destroyDatabaseMigrations()
    {
        Schema::dropIfExists('mysql_explain');
    }

    public function testResultIsAnObject()
    {
        $result = DB::table('mysql_explain')->select()->where('name', 'laravel')->explain();

        $this->assertObjectHasProperty('rows', $result[0]);
        $this->assertObjectHasProperty('select_type', $result[0]);
        $this->assertObjectHasProperty('type', $result[0]);
        $this->assertObjectHasProperty('Extra', $result[0]);
        $this->assertObjectHasProperty('key', $result[0]);
        $this->assertObjectHasProperty('key_len', $result[0]);
        $this->assertObjectHasProperty('partitions', $result[0]);
        $this->assertIsInt($result[0]->rows);
        $this->assertNull($result[0]->partitions);
    }
}
