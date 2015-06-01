<?php

use Mockery as m;
use Illuminate\Console\Command;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Console\Application as ConsoleApplication;
use Illuminate\Contracts\Events\Dispatcher as EventDispatcher;
use Symfony\Component\Console\Command\Command as SymfonyCommand;

class ConsoleApplicationTest extends PHPUnit_Framework_TestCase {

	public function tearDown()
	{
		m::close();
	}


	public function testAddSetsLaravelInstance()
	{
		$app = $this->getMockConsole(['addToParent']);
		$command = m::mock(Command::class);
		$command->shouldReceive('setLaravel')->once()->with(m::type(Application::class));
		$app->expects($this->once())->method('addToParent')->with($this->equalTo($command))->will($this->returnValue($command));
		$result = $app->add($command);

		$this->assertEquals($command, $result);
	}


	public function testLaravelNotSetOnSymfonyCommands()
	{
		$app = $this->getMockConsole(['addToParent']);
		$command = m::mock(SymfonyCommand::class);
		$command->shouldReceive('setLaravel')->never();
		$app->expects($this->once())->method('addToParent')->with($this->equalTo($command))->will($this->returnValue($command));
		$result = $app->add($command);

		$this->assertEquals($command, $result);
	}


	public function testResolveAddsCommandViaApplicationResolution()
	{
		$app = $this->getMockConsole(['addToParent']);
		$command = m::mock(SymfonyCommand::class);
		$app->getLaravel()->shouldReceive('make')->once()->with('foo')->andReturn(m::mock(SymfonyCommand::class));
		$app->expects($this->once())->method('addToParent')->with($this->equalTo($command))->will($this->returnValue($command));
		$result = $app->resolve('foo');

		$this->assertEquals($command, $result);
	}


	protected function getMockConsole(array $methods)
	{
		$app = m::mock(Application::class, ['version' => '5.1']);
		$events = m::mock(EventDispatcher::class, ['fire' => null]);

		$console = $this->getMock(ConsoleApplication::class, $methods, [
			$app, $events, 'test-version'
		]);

		return $console;
	}

}
