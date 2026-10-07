<?php

namespace Illuminate\Tests\Database;

use JMac\Testing\Double;
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
        $connection = Double::for(Connection::class);
        $connection->expects('insert')->with('sql', ['foo']);
        $connection->expects('getPdo')->returns($pdo);
        $builder = Double::for(Builder::class);
        $builder->expects('getConnection')->times(2)->returns($connection);
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
