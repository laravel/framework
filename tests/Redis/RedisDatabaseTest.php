<?php

use Mockery as m;
use Illuminate\Redis\Database;

class RedisDatabaseTest extends PHPUnit_Framework_TestCase {

	public function tearDown()
	{
		m::close();
	}


	public function testConnectMethodConnectsToDatabase()
	{
		$redis = $this->getMock('Illuminate\Redis\Database', array('openSocket', 'command'), array('127.0.0.1', 100));
		$redis->expects($this->once())->method('openSocket');
		$redis->expects($this->once())->method('command')->with($this->equalTo('select'), $this->equalTo(array(0)));

		$redis->connect();
	}


	public function testCommandMethodIssuesCommand()
	{
		$redis = $this->getMock('Illuminate\Redis\Database', array('fileWrite', 'fileGet', 'buildCommand', 'parseResponse'), array('127.0.0.1', 100));
		$redis->expects($this->once())->method('fileWrite')->with($this->equalTo('built'));
		$redis->expects($this->once())->method('buildCommand')->with($this->equalTo('foo'), $this->equalTo(array('bar')))->will($this->returnValue('built'));
		$redis->expects($this->once())->method('fileGet')->with($this->equalTo(512))->will($this->returnValue('results'));
		$redis->expects($this->once())->method('parseResponse')->with($this->equalTo('results'))->will($this->returnValue('parsed'));

		$this->assertEquals('parsed', $redis->command('foo', array('bar')));
	}


	public function testInlineParsing()
	{
		$redis = new Database('127.0.0.1', 100);
		$response = $redis->parseResponse('+OK');

		$this->assertEquals('OK', $response);
	}


	public function testIntegerInlineResponse()
	{
		$redis = new Database('127.0.0.1', 100);
		$response = $redis->parseResponse(":1\r\n");

		$this->assertEquals(1, $response);
	}


	public function testBulkResponse()
	{
		$redis = m::mock('Illuminate\Redis\Database[fileRead]');
		$redis->shouldReceive('fileRead')->once()->with(3)->andReturn('foo');
		$redis->shouldReceive('fileRead')->once()->with(2);

		$this->assertEquals('foo', $redis->parseResponse("$3\r\nfoo\r\n"));
	}


	public function testLongBulkResponse()
	{
		$redis = m::mock('Illuminate\Redis\Database[fileRead]');
		$redis->shouldReceive('fileRead')->once()->with(1024)->andReturn('foo');
		$redis->shouldReceive('fileRead')->once()->with(10)->andReturn('bar');
		$redis->shouldReceive('fileRead')->once()->with(2);

		$this->assertEquals('foobar', $redis->parseResponse("$1034\r\nfoo\r\n"));	
	}


	public function testMultiBulkResponse()
	{
		$redis = m::mock('Illuminate\Redis\Database[fileGet,fileRead]');
		$redis->shouldReceive('fileGet')->twice()->with(512)->andReturn('$3');
		$redis->shouldReceive('fileRead')->twice()->with(3)->andReturn('foo', 'bar');
		$redis->shouldReceive('fileRead')->twice()->with(2);

		$this->assertEquals(array('foo', 'bar'), $redis->parseResponse("*2\r\n$3\r\nfoo\r\n$3\r\nbar\r\n"));	
	}


	public function testCommandsAreBuiltProperly()
	{
		$redis = new Database('127.0.0.1', 100);
		$command = $redis->buildCommand('lpush', array('list', 'taylor'));

		$this->assertEquals("*3\r\n$5\r\nLPUSH\r\n$4\r\nlist\r\n$6\r\ntaylor\r\n", $command);
	}

}