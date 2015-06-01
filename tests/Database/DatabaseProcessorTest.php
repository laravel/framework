<?php

use Mockery as m;
use Illuminate\Database\Connection;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\Processors\Processor;

class DatabaseProcessorTest extends PHPUnit_Framework_TestCase {

	public function tearDown()
	{
		m::close();
	}


	public function testInsertGetIdProcessing()
	{
		$pdo = $this->getMock('ProcessorTestPDOStub');
		$pdo->expects($this->once())->method('lastInsertId')->with($this->equalTo('id'))->will($this->returnValue('1'));
		$connection = m::mock(Connection::class);
		$connection->shouldReceive('insert')->once()->with('sql', array('foo'));
		$connection->shouldReceive('getPdo')->once()->andReturn($pdo);
		$builder = m::mock(Builder::class);
		$builder->shouldReceive('getConnection')->andReturn($connection);
		$result = (new Processor)->processInsertGetId($builder, 'sql', array('foo'), 'id');
		$this->assertSame(1, $result);
	}

}

class ProcessorTestPDOStub extends PDO {

	public function __construct() {}
	public function lastInsertId($sequence = null) {}

}
