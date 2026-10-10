<?php

namespace Illuminate\Tests\Database;

use Illuminate\Database\Connection;
use Illuminate\Database\Query\Grammars\MariaDbGrammar;
use JMac\Testing\Double;
use JMac\Testing\Integrations\PHPUnit\VerifiesDoubles;
use PHPUnit\Framework\TestCase;

class DatabaseMariaDbQueryGrammarTest extends TestCase
{
    use VerifiesDoubles;

    public function testToRawSql()
    {
        $connection = Double::for(Connection::class);
        $connection->expects('escape')->with('foo', false)->returns("'foo'");
        $grammar = new MariaDbGrammar($connection);

        $query = $grammar->substituteBindingsIntoRawSql(
            'select * from "users" where \'Hello\\\'World?\' IS NOT NULL AND "email" = ?',
            ['foo'],
        );

        $this->assertSame('select * from "users" where \'Hello\\\'World?\' IS NOT NULL AND "email" = \'foo\'', $query);
    }
}
