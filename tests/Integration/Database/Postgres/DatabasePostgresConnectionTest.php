<?php

namespace Illuminate\Tests\Integration\Database\Postgres;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RequiresOperatingSystem;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;

#[RequiresOperatingSystem('Linux|Darwin')]
#[RequiresPhpExtension('pdo_pgsql')]
class DatabasePostgresConnectionTest extends PostgresTestCase
{
    protected function afterRefreshingDatabase()
    {
        if (! Schema::hasTable('pgsql_table')) {
            Schema::create('pgsql_table', function (Blueprint $table) {
                $table->json('json_col')->nullable();
                $table->timestamptz('timestamptz', precision: 6)->nullable();
            });
        }
    }

    protected function destroyDatabaseMigrations()
    {
        Schema::drop('pgsql_table');
    }

    #[DataProvider('jsonWhereNullDataProvider')]
    public function testJsonWhereNull($expected, $key, array $value = ['value' => 123])
    {
        DB::table('pgsql_table')->insert(['json_col' => json_encode($value)]);

        $this->assertSame($expected, DB::table('pgsql_table')->whereNull("json_col->$key")->exists());
    }

    #[DataProvider('jsonWhereNullDataProvider')]
    public function testJsonWhereNotNull($expected, $key, array $value = ['value' => 123])
    {
        DB::table('pgsql_table')->insert(['json_col' => json_encode($value)]);

        $this->assertSame(! $expected, DB::table('pgsql_table')->whereNotNull("json_col->$key")->exists());
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
        DB::table('pgsql_table')->insert([
            ['json_col' => '{"foo":["bar"]}'],
            ['json_col' => '{"foo":["baz"]}'],
            ['json_col' => '{"foo":[["array"]]}'],
        ]);

        $updatedCount = DB::table('pgsql_table')->where('json_col->foo[0]', 'baz')->update([
            'json_col->foo[0]' => 'updated',
        ]);
        $this->assertSame(1, $updatedCount);

        $updatedCount = DB::table('pgsql_table')->where('json_col->foo[0][0]', 'array')->update([
            'json_col->foo[0][0]' => 'updated',
        ]);
        $this->assertSame(1, $updatedCount);
    }

    #[DataProvider('jsonContainsKeyDataProvider')]
    public function testWhereJsonContainsKey($count, $column)
    {
        DB::table('pgsql_table')->insert([
            ['json_col' => '{"foo":{"bar":["baz"]}}'],
            ['json_col' => '{"foo":{"bar":false}}'],
            ['json_col' => '{"foo":{}}'],
            ['json_col' => '{"foo":[{"bar":"bar"},{"baz":"baz"}]}'],
            ['json_col' => '{"bar":null}'],
        ]);

        $this->assertSame($count, DB::table('pgsql_table')->whereJsonContainsKey($column)->count());
    }

    public function testDateTimeInterfacesAreNotTruncated()
    {
        $datetime = Carbon::parse('2021-01-01 12:34:56.123456', 'America/New_York');

        DB::table('pgsql_table')->insert([['timestamptz' => $datetime]]);

        $this->assertSame(
            '2021-01-01 17:34:56.123456+00',
            DB::table('pgsql_table')->pluck('timestamptz')->first(),
        );
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
