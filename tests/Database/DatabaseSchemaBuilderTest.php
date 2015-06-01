<?php

use Mockery as m;
use Illuminate\Database\Connection;
use Illuminate\Database\Schema\Builder;

class DatabaseSchemaBuilderTest extends PHPUnit_Framework_TestCase {

	public function tearDown()
	{
		m::close();
	}


	public function testHasTableCorrectlyCallsGrammar()
	{
		$connection = m::mock(Connection::class);
		$grammar = m::mock('StdClass');
		$connection->shouldReceive('getSchemaGrammar')->andReturn($grammar);
		$builder = new Builder($connection);
		$grammar->shouldReceive('compileTableExists')->once()->andReturn('sql');
		$connection->shouldReceive('getTablePrefix')->once()->andReturn('prefix_');
		$connection->shouldReceive('select')->once()->with('sql', array('prefix_table'))->andReturn(array('prefix_table'));

		$this->assertTrue($builder->hasTable('table'));
	}


	public function testTableHasColumns()
    	{
        	$connection = m::mock(Connection::class);
        	$grammar = m::mock('StdClass');
        	$connection->shouldReceive('getSchemaGrammar')->andReturn($grammar);
        	$builder = m::mock(Builder::class.'[getColumnListing]', array($connection));
        	$builder->shouldReceive('getColumnListing')->with('users')->twice()->andReturn(array('id', 'firstname'));

        	$this->assertTrue($builder->hasColumns('users', array('id', 'firstname')));
        	$this->assertFalse($builder->hasColumns('users', array('id', 'address')));
    	}
}
