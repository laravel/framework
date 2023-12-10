<?php

namespace Illuminate\Tests\Integration\Database\Postgres;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * @requires extension pdo_pgsql
 * @requires OS Linux|Darwin
 */
class DatabasePostgresConnectionTest extends PostgresTestCase
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
     * @dataProvider jsonWhereNullDataProvider
     */
    public function testJsonWhereNull($expected, $key, array $value = ['value' => 123])
    {
        DB::table('json_table')->insert(['json_col' => json_encode($value)]);

        $this->assertSame($expected, DB::table('json_table')->whereNull("json_col->$key")->exists());
    }

    /**
     * @dataProvider jsonWhereNullDataProvider
     */
    public function testJsonWhereNotNull($expected, $key, array $value = ['value' => 123])
    {
        DB::table('json_table')->insert(['json_col' => json_encode($value)]);

        $this->assertSame(! $expected, DB::table('json_table')->whereNotNull("json_col->$key")->exists());
    }

    public static function jsonWhereNullDataProvider()
    {
        return [
            'key not exists' => [true, 'invalid'],
            'key exists and null' => [true, 'value', ['value' => null]],
            'key exists and "null"' => [false, 'value', ['value' => 'null']],
            'key exists and not null' => [false, 'value', ['value' => false]],
            'nested key not exists' => [true, 'nested->invalid'],
            'nested key exists and null' => [true, 'nested->value', ['nested' => ['value' => null]]],
            'nested key exists and "null"' => [false, 'nested->value', ['nested' => ['value' => 'null']]],
            'nested key exists and not null' => [false, 'nested->value', ['nested' => ['value' => false]]],
            'array index not exists' => [true, '[0]', [1 => 'invalid']],
            'array index exists and null' => [true, '[0]', [null]],
            'array index exists and "null"' => [false, '[0]', ['null']],
            'array index exists and not null' => [false, '[0]', [false]],
            'multiple array index not exists' => [true, '[0][0]', [1 => [1 => 'invalid']]],
            'multiple array index exists and null' => [true, '[0][0]', [[null]]],
            'multiple array index exists and "null"' => [false, '[0][0]', [['null']]],
            'multiple array index exists and not null' => [false, '[0][0]', [[false]]],
            'nested array index not exists' => [true, 'nested[0]', ['nested' => [1 => 'nested->invalid']]],
            'nested array index exists and null' => [true, 'nested->value[1]', ['nested' => ['value' => [0, null]]]],
            'nested array index exists and "null"' => [false, 'nested->value[1]', ['nested' => ['value' => [0, 'null']]]],
            'nested array index exists and not null' => [false, 'nested->value[1]', ['nested' => ['value' => [0, false]]]],
        ];
    }

    public function testJsonPathUpdate()
    {
        DB::table('json_table')->insert([
            ['json_col' => '{"foo":["bar"]}'],
            ['json_col' => '{"foo":["baz"]}'],
            ['json_col' => '{"foo":[["array"]]}'],
        ]);

        $updatedCount = DB::table('json_table')->where('json_col->foo[0]', 'baz')->update([
            'json_col->foo[0]' => 'updated',
        ]);
        $this->assertSame(1, $updatedCount);

        $updatedCount = DB::table('json_table')->where('json_col->foo[0][0]', 'array')->update([
            'json_col->foo[0][0]' => 'updated',
        ]);
        $this->assertSame(1, $updatedCount);
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
