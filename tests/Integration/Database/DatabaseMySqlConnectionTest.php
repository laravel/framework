<?php

namespace Illuminate\Tests\Integration\Database;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DatabaseMySqlConnectionTest extends DatabaseMySqlTestCase
{
    const TABLE = 'player';
    const FLOAT_COL = 'float_col';
    const JSON_COL = 'json_col';
    const FLOAT_VAL = 0.2;

    protected function setUp(): void
    {
        parent::setUp();

        if (! Schema::hasTable(self::TABLE)) {
            Schema::create(self::TABLE, function (Blueprint $table) {
                $table->json(self::JSON_COL)->nullable();
                $table->float(self::FLOAT_COL)->nullable();
            });
        }
    }

    protected function tearDown(): void
    {
        DB::table(self::TABLE)->truncate();

        parent::tearDown();
    }

    /**
     * @dataProvider floatComparisonsDataProvider
     *
     * @param  float  $value        the value to compare against the JSON value
     * @param  string  $operator    the comparison operator to use. e.g. '<', '>', '='
     * @param  bool  $shouldMatch   true if the comparison should match, false if not
     */
    public function testJsonFloatComparison(float $value, string $operator, bool $shouldMatch): void
    {
        DB::table(self::TABLE)->insert([self::JSON_COL => '{"rank":'.self::FLOAT_VAL.'}']);

        $this->assertSame(
            $shouldMatch,
            DB::table(self::TABLE)->where(self::JSON_COL.'->rank', $operator, $value)->exists(),
            self::JSON_COL.'->rank should '.($shouldMatch ? '' : 'not ')."be $operator $value"
        );
    }

    public function floatComparisonsDataProvider(): array
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

    public function testFloatValueStoredCorrectly(): void
    {
        DB::table(self::TABLE)->insert([self::FLOAT_COL => self::FLOAT_VAL]);

        $this->assertEquals(self::FLOAT_VAL, DB::table(self::TABLE)->value(self::FLOAT_COL));
    }

    /**
     * @dataProvider jsonWhereNullDataProvider
     */
    public function testJsonWhereNull(bool $expected, string $key, array $value = ['value' => 123]): void
    {
        DB::table(self::TABLE)->insert([self::JSON_COL => json_encode($value)]);

        $this->assertSame($expected, DB::table(self::TABLE)->whereNull(self::JSON_COL.'->'.$key)->exists());
    }

    /**
     * @dataProvider jsonWhereNullDataProvider
     */
    public function testJsonWhereNotNull(bool $expected, string $key, array $value = ['value' => 123]): void
    {
        DB::table(self::TABLE)->insert([self::JSON_COL => json_encode($value)]);

        $this->assertSame(! $expected, DB::table(self::TABLE)->whereNotNull(self::JSON_COL.'->'.$key)->exists());
    }

    public function jsonWhereNullDataProvider(): array
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
        ];
    }
}
