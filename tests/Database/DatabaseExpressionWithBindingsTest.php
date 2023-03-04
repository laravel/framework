<?php

namespace Illuminate\Tests\Database;

use Illuminate\Database\Query\ExpressionWithBindings;
use Illuminate\Database\Query\Grammars\Grammar;
use PHPUnit\Framework\TestCase;

class DatabaseExpressionWithBindingsTest extends TestCase
{
    protected Grammar $grammar;

    protected function setUp(): void
    {
        parent::setUp();

        $this->grammar = new Grammar;
    }

    public function testPropertyRetrieval(): void
    {
        $sql = 'unaccent(?)';
        $bindings = ['term'];

        $expression = new ExpressionWithBindings($sql, $bindings);

        $this->assertEquals($expression->getValue($this->grammar), $sql);
        $this->assertEquals($expression->getBindings($this->grammar), $bindings);
    }
}
