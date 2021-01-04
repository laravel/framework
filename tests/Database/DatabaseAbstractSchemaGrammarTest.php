<?php

namespace Illuminate\Tests\Database;

use Illuminate\Database\Connection;
use Illuminate\Database\Schema\Grammars\Grammar;
use LogicException;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class DatabaseAbstractSchemaGrammarTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
    }

    public function testCreateDatabaseIfNotExists()
    {
        $grammar = new class extends Grammar {};

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('This database driver does not support create databases.');

        $grammar->compileCreateDatabaseIfNotExists('foo', m::mock(Connection::class));
    }

    public function testDropDatabaseIfExists()
    {
        $grammar = new class extends Grammar {};

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('This database driver does not support drop databases.');

        $grammar->compileDropDatabaseIfExists('foo');
    }
}
