<?php

namespace Illuminate\Tests\Integration\Database;

use Illuminate\Database\InvalidValueException;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\Attributes\RequiresDatabase;
use Throwable;

class InvalidValueExceptionTest extends DatabaseTestCase
{
    protected function afterRefreshingDatabase()
    {
        Schema::create('test_invalid_value', function (Blueprint $table) {
            $table->integer('id')->primary();
            $table->string('name');
        });

        DB::table('test_invalid_value')->insert(['id' => 12, 'name' => 'Taylor']);
    }

    private function captureFrom(callable $callback): Throwable
    {
        try {
            $callback();
        } catch (Throwable $e) {
            return $e;
        }

        $this->fail('No exception was thrown.');
    }

    #[RequiresDatabase('pgsql')]
    public function testValuesThatAreNotValidForTheColumnTypeArePromoted()
    {
        $e = $this->captureFrom(fn () => DB::table('test_invalid_value')->where('id', 'abc')->first());

        $this->assertInstanceOf(InvalidValueException::class, $e);
        $this->assertSame('22P02', $e->getCode());
    }

    #[RequiresDatabase('pgsql')]
    public function testValuesOutOfRangeForTheColumnWidthArePromoted()
    {
        $e = $this->captureFrom(fn () => DB::table('test_invalid_value')->where('id', '3000000000')->first());

        $this->assertInstanceOf(InvalidValueException::class, $e);
        $this->assertSame('22003', $e->getCode());
    }

    #[RequiresDatabase('pgsql')]
    public function testValuesWithinTheColumnWidthAreNotPromoted()
    {
        $this->assertNull(DB::table('test_invalid_value')->where('id', '2000000000')->first());
        $this->assertSame('Taylor', DB::table('test_invalid_value')->where('id', '12')->first()->name);
    }

    #[RequiresDatabase('pgsql')]
    public function testUndefinedColumnsAreNotPromoted()
    {
        $e = $this->captureFrom(fn () => DB::table('test_invalid_value')->where('missing_column', 'x')->first());

        $this->assertInstanceOf(QueryException::class, $e);
        $this->assertNotInstanceOf(InvalidValueException::class, $e);
    }

    public function testUndefinedTablesAreNotPromoted()
    {
        $e = $this->captureFrom(fn () => DB::table('table_that_does_not_exist')->where('id', 1)->first());

        $this->assertInstanceOf(QueryException::class, $e);
        $this->assertNotInstanceOf(InvalidValueException::class, $e);
    }

    public function testSyntaxErrorsAreNotPromoted()
    {
        $e = $this->captureFrom(fn () => DB::select('selct * from test_invalid_value'));

        $this->assertInstanceOf(QueryException::class, $e);
        $this->assertNotInstanceOf(InvalidValueException::class, $e);
    }
}
