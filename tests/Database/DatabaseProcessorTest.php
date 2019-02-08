<?php

namespace Illuminate\Tests\Database;

use PDO;
use Mockery as m;
use PHPUnit\Framework\TestCase;
use Illuminate\Database\Connection;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\Processors\Processor;

class DatabaseProcessorTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
    }

    public function testInsertGetIdProcessing()
    {
        $pdo = $this->createMock(ProcessorTestPDOStub::class);
        $pdo->expects($this->once())->method('lastInsertId')->with($this->equalTo('id'))->will($this->returnValue('1'));
        $connection = m::mock(Connection::class);
        $connection->shouldReceive('insert')->once()->with('sql', ['foo']);
        $connection->shouldReceive('getPdo')->once()->andReturn($pdo);
        $builder = m::mock(Builder::class);
        $builder->shouldReceive('getConnection')->andReturn($connection);
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

    public function lastInsertId($sequence = null)
    {
        //
    }
}
