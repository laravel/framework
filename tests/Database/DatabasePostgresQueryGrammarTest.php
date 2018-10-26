<?php

namespace Illuminate\Tests\Database;

use PHPUnit\Framework\TestCase;
use Illuminate\Database\Query\Grammars\PostgresGrammar;

class DatabasePostgresQueryGrammarTest extends TestCase
{
    public function testFormatBoolValue()
    {
        $grammar = new PostgresGrammar();
        $this->assertTrue($grammar->formatBoolValue(true));
        $this->assertFalse($grammar->formatBoolValue(false));
    }
}
