<?php

namespace Illuminate\Tests\Integration\Database\MySql;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * @requires extension pdo_mysql
 * @requires OS Linux|Darwin
 */
class DatabaseMySqlConnectionTest extends MySqlTestCase
{
    const TABLE = 'player';
    const FLOAT_COL = 'float_col';
    const JSON_COL = 'json_col';
    const FLOAT_VAL = 0.2;

    protected function afterRefreshingDatabase()
    {
        if (! Schema::hasTable(self::TABLE)) {
            Schema::create(self::TABLE, function (Blueprint $table) {
                $table->json(self::JSON_COL)->nullable();
                $table->float(self::FLOAT_COL)->nullable();
            });
        }
    }

    protected function destroyDatabaseMigrations()
    {
        Schema::drop(self::TABLE);
    }

    /**
     * @dataProvider floatComparisonsDataProvider
     */
    public function testJsonFloatComparison($value, $operator, $shouldMatch)
    {
        DB::table(self::TABLE)->insert([self::JSON_COL => '{"rank":'.self::FLOAT_VAL.'}']);

        $this->assertSame(
            $shouldMatch,
            DB::table(self::TABLE)->where(self::JSON_COL.'->rank', $operator, $value)->exists(),
            self::JSON_COL.'->rank should '.($shouldMatch ? '' : 'not ')."be $operator $value"
        );
    }

    public static function floatComparisonsDataProvider()
    {
        return [
            [0.2, '=', true],
            [0.2, '>', false],
            [0.2, '<', false],
            [0.1, '=', false],
            [0.1, '<', false],
            [0.1, '>', true],
            [0.3, '=', false],
            [0.3, '<', true],
            [0.3, '>', false],
        ];
    }

    public function testFloatValueStoredCorrectly()
    {
        DB::table(self::TABLE)->insert([self::FLOAT_COL => self::FLOAT_VAL]);

        $this->assertEquals(self::FLOAT_VAL, DB::table(self::TABLE)->value(self::FLOAT_COL));
    }

    /**
     * @dataProvider jsonWhereNullDataProvider
     */
    public function testJsonWhereNull($expected, $key, array $value = ['value' => 123])
    {
        DB::table(self::TABLE)->insert([self::JSON_COL => json_encode($value)]);

        $this->assertSame($expected, DB::table(self::TABLE)->whereNull(self::JSON_COL.'->'.$key)->exists());
    }

    /**
     * @dataProvider jsonWhereNullDataProvider
     */
    public function testJsonWhereNotNull($expected, $key, array $value = ['value' => 123])
    {
        DB::table(self::TABLE)->insert([self::JSON_COL => json_encode($value)]);

        $this->assertSame(! $expected, DB::table(self::TABLE)->whereNotNull(self::JSON_COL.'->'.$key)->exists());
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
            'array index not exists' => [false, '[0]', [1 => 'invalid']],
            'array index exists and null' => [true, '[0]', [null]],
            'array index exists and "null"' => [false, '[0]', ['null']],
            'array index exists and not null' => [false, '[0]', [false]],
            'nested array index not exists' => [false, 'nested[0]', ['nested' => [1 => 'nested->invalid']]],
            'nested array index exists and null' => [true, 'nested->value[1]', ['nested' => ['value' => [0, null]]]],
            'nested array index exists and "null"' => [false, 'nested->value[1]', ['nested' => ['value' => [0, 'null']]]],
            'nested array index exists and not null' => [false, 'nested->value[1]', ['nested' => ['value' => [0, false]]]],
        ];
    }

    public function testJsonPathUpdate()
    {
        DB::table(self::TABLE)->insert([
            [self::JSON_COL => '{"foo":["bar"]}'],
            [self::JSON_COL => '{"foo":["baz"]}'],
        ]);
        $updatedCount = DB::table(self::TABLE)->where(self::JSON_COL.'->foo[0]', 'baz')->update([
            self::JSON_COL.'->foo[0]' => 'updated',
        ]);
        $this->assertSame(1, $updatedCount);
    }

    /**
     * @dataProvider jsonContainsKeyDataProvider
     */
    public function testWhereJsonContainsKey($count, $column)
    {
        DB::table(self::TABLE)->insert([
            ['json_col' => '{"foo":{"bar":["baz"]}}'],
            ['json_col' => '{"foo":{"bar":false}}'],
            ['json_col' => '{"foo":{}}'],
            ['json_col' => '{"foo":[{"bar":"bar"},{"baz":"baz"}]}'],
            ['json_col' => '{"bar":null}'],
        ]);

        $this->assertSame($count, DB::table(self::TABLE)->whereJsonContainsKey($column)->count());
    }

    public static function jsonContainsKeyDataProvider()
    {
        return [
            'string key' => [4, 'json_col->foo'],
            'nested key exists' => [2, 'json_col->foo->bar'],
            'string key missing' => [0, 'json_col->none'],
            'integer key with arrow ' => [0, 'json_col->foo->bar->0'],
            'integer key with braces' => [2, 'json_col->foo->bar[0]'],
            'integer key missing' => [0, 'json_col->foo->bar[1]'],
            'mixed keys' => [1, 'json_col->foo[1]->baz'],
            'null value' => [1, 'json_col->bar'],
        ];
    }
}
