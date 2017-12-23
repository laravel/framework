<?php

namespace Illuminate\Tests\Database;

use Mockery as m;
use PHPUnit\Framework\TestCase;

class DatabaseProcessorTest extends TestCase
{
    public function tearDown()
    {
        m::close();
    }

    public function testInsertGetIdProcessing()
    {
        $pdo = $this->createMock('Illuminate\Tests\Database\ProcessorTestPDOStub');
        $pdo->expects($this->once())->method('lastInsertId')->with($this->equalTo('id'))->will($this->returnValue('1'));
        $connection = m::mock('Illuminate\Database\Connection');
        $connection->shouldReceive('insert')->once()->with('sql', ['foo']);
        $connection->shouldReceive('getPdo')->once()->andReturn($pdo);
        $builder = m::mock('Illuminate\Database\Query\Builder');
        $builder->shouldReceive('getConnection')->andReturn($connection);
        $processor = new \Illuminate\Database\Query\Processors\Processor;
        $result = $processor->processInsertGetId($builder, 'sql', ['foo'], 'id');
        $this->assertSame(1, $result);
    }
}

class ProcessorTestPDOStub extends \PDO
{
    public function __construct()
    {
    }

    public function lastInsertId($sequence = null)
    {
    }
}
