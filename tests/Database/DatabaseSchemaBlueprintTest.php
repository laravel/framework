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
		$blueprint = $this->getMock('Illuminate\Database\Schema\Blueprint', array('toSql'), array('users'));
		$blueprint->expects($this->once())->method('toSql')->with($this->equalTo($conn), $this->equalTo($grammar))->will($this->returnValue(array('foo', 'bar')));

		$blueprint->build($conn, $grammar);
	}


	public function testIndexDefaultNames()
	{
		$blueprint = new Blueprint('users');
		$blueprint->unique(array('foo', 'bar'));
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
		$blueprint->dropUnique(array('foo', 'bar'));
		$commands = $blueprint->getCommands();
		$this->assertEquals('users_foo_bar_unique', $commands[0]->index);

		$blueprint = new Blueprint('users');
		$blueprint->dropIndex(array('foo'));
		$commands = $blueprint->getCommands();
		$this->assertEquals('users_foo_index', $commands[0]->index);
	}


	public function testCustomType()
	{
		$conn      = m::mock('Illuminate\Database\Connection');
		$grammar   = new \Illuminate\Database\Schema\Grammars\MySqlGrammar();
		$blueprint = new Blueprint('user');
		$blueprint->string('teststr', 200);
		$blueprint->custom('mycolumn', function ($column)
		{
			return 'MY_CUSTOM_COLUMN_TYPE';
		});

		$this->assertEquals('alter table `user` add `teststr` varchar(200) not null, add `mycolumn` MY_CUSTOM_COLUMN_TYPE not null',
			$blueprint->toSql($conn, $grammar)[0]);
	}

}
