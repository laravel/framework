<?php

use \Illuminate\Database\Query\CompiledQuery;

class DatabaseQueryCompiledQueryTest extends PHPUnit_Framework_TestCase {

	public function testConstruction()
	{
		$compiled = new CompiledQuery('select *', [1]);
		$this->assertEquals('select *', $compiled->sql);
		$this->assertEquals([1], $compiled->bindings);
	}


	public function testSimpleConcatenation()
	{
		$compiled = new CompiledQuery('select *');
		$compiled->concatenate(new CompiledQuery('from users'));
		$this->assertEquals('select * from users', $compiled->sql);
	}


	public function testConcatenationHandlesEmptyAndNullStrings()
	{
		$select = new CompiledQuery('select *');
		$select->concatenate(new CompiledQuery);
		$this->assertEquals('select *', $select->sql);

		$select->concatenate(new CompiledQuery(''));
		$this->assertEquals('select *', $select->sql);

		$null = new CompiledQuery;
		$null->concatenate($select);
		$this->assertEquals('select *', $null->sql);

		$empty = new CompiledQuery('');
		$empty->concatenate($select);
		$this->assertEquals('select *', $empty->sql);

		$null = new CompiledQuery;
		$null->concatenate(new CompiledQuery());
		$this->assertEquals('', $null->sql);
	}


	public function testGluedConcatenation()
	{
		$compiled = new CompiledQuery('order by name');
		$compiled->concatenate(new CompiledQuery('age'), ', ');
		$this->assertEquals('order by name, age', $compiled->sql);
	}


	public function testConcatenationTrims()
	{
		$compiled = new CompiledQuery(' select * ');
		$compiled->concatenate(new CompiledQuery(' from users '));
		$this->assertEquals('select * from users', $compiled->sql);
	}


	public function testSimpleBindingMerge()
	{
		$compiled = new CompiledQuery('', [1]);
		$compiled->concatenate(new CompiledQuery('', [2]));
		$this->assertEquals([1,2], $compiled->bindings);
	}

	public function testConcatenationHandlesEmptyAndNullBindings()
	{
		$select = new CompiledQuery('', [1]);
		$select->concatenate(new CompiledQuery);
		$this->assertEquals([1], $select->bindings);

		$select->concatenate(new CompiledQuery('', null));
		$this->assertEquals([1], $select->bindings);

		$null = new CompiledQuery;
		$null->concatenate($select);
		$this->assertEquals([1], $select->bindings);

		$empty = new CompiledQuery('', []);
		$empty->concatenate($select);
		$this->assertEquals([1], $select->bindings);

		$null = new CompiledQuery;
		$null->concatenate(new CompiledQuery);
		$this->assertEquals([], $null->bindings);
	}

	public function testFullConcatenate()
	{
		$select = new CompiledQuery('select ?', ['*']);
		$from = new CompiledQuery('from users');
		$where = new CompiledQuery('where email = ?', ['you@email.com']);
		$select->concatenate($from)->concatenate($where);
		$this->assertEquals('select ? from users where email = ?', $select->sql);
		$this->assertEquals(['*', 'you@email.com'], $select->bindings);
	}

	public function testConcatenateReturnsSelf()
	{
		$select = new CompiledQuery('select *');
		$return = $select->concatenate(new CompiledQuery);
		$this->assertEquals($select, $return);
	}

	public function testConcatenateDoesNotMutatePassedInCompiledQuery()
	{
		$select = new CompiledQuery('select *');
		$from = new CompiledQuery('from users', [1]);
		$select->concatenate($from);
		$this->assertEquals('from users', $from->sql);
		$this->assertEquals([1], $from->bindings);
	}

} 