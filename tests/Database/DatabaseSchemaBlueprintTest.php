<?php

use Mockery as m;
use Illuminate\Database\Schema\Blueprint;

class DatabaseSchemaBlueprintTest extends PHPUnit_Framework_TestCase {

	public function tearDown()
	{
		m::close();
	}


	public function testToSqlRunsCommandsFromBlueprint()
	{
		$conn = m::mock('Illuminate\Database\Connection');
		$conn->shouldReceive('statement')->once()->with('foo');
		$conn->shouldReceive('statement')->once()->with('bar');
		$grammar = m::mock('Illuminate\Database\Schema\Grammars\MySqlGrammar');
		$blueprint = $this->getMock('Illuminate\Database\Schema\Blueprint', ['toSql'], ['users']);
		$blueprint->expects($this->once())->method('toSql')->with($this->equalTo($conn), $this->equalTo($grammar))->will($this->returnValue(['foo', 'bar']));

		$blueprint->build($conn, $grammar);
	}


	public function testIndexDefaultNames()
	{
		$blueprint = new Blueprint('users');
		$blueprint->unique(['foo', 'bar']);
		$commands = $blueprint->getCommands();
		$this->assertEquals('users_foo_bar_unique', $commands[0]->index);

		$blueprint = new Blueprint('users');
		$blueprint->index('foo');
		$commands = $blueprint->getCommands();
		$this->assertEquals('users_foo_index', $commands[0]->index);
	}


	public function testDropIndexDefaultNames()
	{
		$blueprint = new Blueprint('users');
		$blueprint->dropUnique(['foo', 'bar']);
		$commands = $blueprint->getCommands();
		$this->assertEquals('users_foo_bar_unique', $commands[0]->index);

		$blueprint = new Blueprint('users');
		$blueprint->dropIndex(['foo']);
		$commands = $blueprint->getCommands();
		$this->assertEquals('users_foo_index', $commands[0]->index);
	}


}
