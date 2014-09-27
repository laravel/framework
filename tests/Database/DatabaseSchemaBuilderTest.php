<?php

use Mockery as m;
use Illuminate\Database\Schema\Builder;

class DatabaseSchemaBuilderTest extends PHPUnit_Framework_TestCase {

	public function tearDown()
	{
		m::close();
	}


	public function testHasTableCorrectlyCallsGrammar()
	{
		$connection = m::mock('Illuminate\Database\Connection');
		$grammar = m::mock('StdClass');
		$connection->shouldReceive('getSchemaGrammar')->andReturn($grammar);
		$builder = new Builder($connection);
		$grammar->shouldReceive('compileTableExists')->once()->andReturn('sql');
		$connection->shouldReceive('getTablePrefix')->once()->andReturn('prefix_');
		$connection->shouldReceive('select')->once()->with('sql', ['prefix_table'])->andReturn(['prefix_table']);

		$this->assertTrue($builder->hasTable('table'));
	}

}
