<?php

namespace Illuminate\Tests\Database;

use Illuminate\Database\Connection;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\Processors\Processor;
use Mockery;
use PDO;
use PHPUnit\Framework\TestCase;

class DatabaseProcessorTest extends TestCase
{
    public function testInsertGetIdProcessing()
    {
        $pdo = $this->createMock(ProcessorTestPDOStub::class);
        $pdo->expects($this->once())->method('lastInsertId')->with('id')->willReturn('1');
        $connection = Mockery::mock(Connection::class);
        $connection->expects('insert')->with('sql', ['foo']);
        $connection->expects('getPdo')->andReturn($pdo);
        $builder = Mockery::mock(Builder::class);
        $builder->expects('getConnection')->twice()->andReturn($connection);
        $processor = new Processor;
        $result = $processor->processInsertGetId($builder, 'sql', ['foo'], 'id');
        $this->assertSame(1, $result);
    }
}

class ProcessorTestPDOStub extends PDO
{
    public function __construct()
    {
        //
    }

    public function lastInsertId($sequence = null): string|false
    {
        return '';
    }
}
