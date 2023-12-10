<?php

namespace Illuminate\Tests\Integration\Database\SqlServer;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * @requires extension pdo_sqlsrv
 * @requires OS Linux|Darwin
 */
class DatabaseSqlServerConnectionTest extends SqlServerTestCase
{
    protected function afterRefreshingDatabase()
    {
        if (! Schema::hasTable('json_table')) {
            Schema::create('json_table', function (Blueprint $table) {
                $table->json('json_col')->nullable();
            });
        }
    }

    protected function destroyDatabaseMigrations()
    {
        Schema::drop('json_table');
    }

    /**
     * @dataProvider jsonContainsKeyDataProvider
     */
    public function testWhereJsonContainsKey($count, $column)
    {
        DB::table('json_table')->insert([
            ['json_col' => '{"foo":{"bar":["baz"]}}'],
            ['json_col' => '{"foo":{"bar":false}}'],
            ['json_col' => '{"foo":{}}'],
            ['json_col' => '{"foo":[{"bar":"bar"},{"baz":"baz"}]}'],
            ['json_col' => '{"bar":null}'],
        ]);

        $this->assertSame($count, DB::table('json_table')->whereJsonContainsKey($column)->count());
    }

    public static function jsonContainsKeyDataProvider()
    {
        return [
            'string key' => [4, 'json_col->foo'],
            'nested key exists' => [2, 'json_col->foo->bar'],
            'string key missing' => [0, 'json_col->none'],
            'integer key with arrow ' => [1, 'json_col->foo->bar->0'],
            'integer key with braces' => [1, 'json_col->foo->bar[0]'],
            'integer key missing' => [0, 'json_col->foo->bar[1]'],
            'mixed keys' => [1, 'json_col->foo[1]->baz'],
            'null value' => [1, 'json_col->bar'],
        ];
    }
}
