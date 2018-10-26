<?php

namespace Illuminate\Tests\Database;

use PHPUnit\Framework\TestCase;
use Illuminate\Database\Query\Grammars\Grammar;

class DatabaseQueryGrammarTest extends TestCase
{
    public function testFormatBoolValue()
    {
        $grammar = new Grammar();
        $this->assertSame(1, $grammar->formatBoolValue(true));
        $this->assertSame(0, $grammar->formatBoolValue(false));
    }
}
