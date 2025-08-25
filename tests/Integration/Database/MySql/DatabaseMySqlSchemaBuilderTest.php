<?php

namespace Illuminate\Tests\Integration\Database\MySql;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\Attributes\RequiresDatabase;
use PHPUnit\Framework\Attributes\RequiresOperatingSystem;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;

#[RequiresOperatingSystem('Linux|Darwin')]
#[RequiresPhpExtension('pdo_mysql')]
class DatabaseMySqlSchemaBuilderTest extends MySqlTestCase
{
    public function testAddCommentToTable()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->comment('This is a comment');
        });

        $tableInfo = DB::table('information_schema.tables')
            ->where('table_schema', $this->app['config']->get('database.connections.mysql.database'))
            ->where('table_name', 'users')
            ->select('table_comment as table_comment')
            ->first();

        $this->assertEquals('This is a comment', $tableInfo->table_comment);

        Schema::drop('users');
    }

    #[RequiresDatabase('mysql', '>=8.0.13')]
    public function testGetRawIndex()
    {
        Schema::create('table', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->rawIndex('(year(created_at))', 'table_raw_index');
        });

        $indexes = Schema::getIndexes('table');

        $this->assertSame([], collect($indexes)->firstWhere('name', 'table_raw_index')['columns']);
    }
}
