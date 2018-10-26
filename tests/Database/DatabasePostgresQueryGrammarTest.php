<?php

namespace Illuminate\Tests\Database;

use Illuminate\Database\Query\Grammars\PostgresGrammar;
use PHPUnit\Framework\TestCase;

class DatabasePostgresQueryGrammarTest extends TestCase
{
    public function testFormatBoolValue()
    {
        $grammar = new PostgresGrammar();
        $this->assertTrue($grammar->formatBoolValue(true));
        $this->assertFalse($grammar->formatBoolValue(false));
    }
}
