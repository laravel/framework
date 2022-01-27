<?php

namespace Illuminate\Tests\Database;

use Carbon\Carbon;
use Illuminate\Database\Query\Expression;
use Illuminate\Database\Schema\Grammars\Grammar;
use Illuminate\Tests\Database\stubs\TestEnum;
use Illuminate\Tests\Database\stubs\TestIntEnum;
use Mockery as m;
use PHPUnit\Framework\TestCase;
use stdClass;

class DatabaseAbstractSchemaGrammarDefaultValueTest extends TestCase
{
    /**
     * @var Grammar
     */
    protected $grammar;

    protected function setUp(): void
    {
        parent::setUp();

        $this->grammar = new class extends Grammar {
            public function testGetDefaultValue($value)
            {
                return $this->getDefaultValue($value);
            }
        };
    }

    protected function tearDown(): void
    {
        m::close();
    }

    public function testString(): void
    {
        $this->assertValue('laravel', 'laravel');
    }

    public function testExpression(): void
    {
        $result = $this->grammar->testGetDefaultValue(new Expression('it'));
        $this->assertEquals('it', $result);
    }

    public function testObjectWithToString(): void
    {
        $value = new class extends stdClass {
            public function __toString()
            {
                return 'just';
            }
        };

        $this->assertValue('just', $value);
    }

    /**
     * @requires PHP >= 8.1
     */
    public function testEnumWithString(): void
    {
        $this->assertValue('test', TestEnum::test);
    }

    /**
     * @requires PHP >= 8.1
     */
    public function testEnumWithInt(): void
    {
        $this->assertValue('1', TestIntEnum::test);
    }

    public function testCarbon(): void
    {
        $this->assertValue('2022-01-27 00:00:00', Carbon::create(2022, 1, 27));
    }

    public function testObjectWithoutToStringMethodFails(): void
    {
        $this->expectExceptionMessage('Object of class stdClass could not be converted to string');
        $this->grammar->testGetDefaultValue(new stdClass());
    }

    protected function assertValue(string $expected, $value): void
    {
        $result = $this->grammar->testGetDefaultValue($value);

        $this->assertEquals("'" . $expected . "'", $result);
    }
}
