<?php

use Mockery as m;
use Illuminate\Cache\CacheManager;
use Illuminate\Foundation\Application;
use Illuminate\Cache\Console\ClearCommand;
use Illuminate\Contracts\Cache\Repository;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;

class ClearCommandTest extends PHPUnit_Framework_TestCase {

	public function tearDown()
	{
		m::close();
	}


	public function testClearWithNoStoreOption()
	{
		$command = new ClearCommandTestStub($cacheManager = m::mock(CacheManager::class));

		$cacheRepository = m::mock(Repository::class);

		$command->setLaravel(new Application);

		$cacheManager->shouldReceive('store')->once()->with(null)->andReturn($cacheRepository);
		$cacheRepository->shouldReceive('flush')->once();

		$this->runCommand($command);
	}


	public function testClearWithStoreOption()
	{
		$command = new ClearCommandTestStub($cacheManager = m::mock(CacheManager::class));

		$cacheRepository = m::mock(Repository::class);

		$command->setLaravel(new Application);

		$cacheManager->shouldReceive('store')->once()->with('foo')->andReturn($cacheRepository);
		$cacheRepository->shouldReceive('flush')->once();

		$this->runCommand($command, ['store' => 'foo']);
	}


	public function testClearWithInvalidStoreOption()
	{
		$command = new ClearCommandTestStub($cacheManager = m::mock(CacheManager::class));

		$cacheRepository = m::mock(Repository::class);

		$command->setLaravel(new Application);

		$cacheManager->shouldReceive('store')->once()->with('bar')->andThrow(InvalidArgumentException::class);
		$cacheRepository->shouldReceive('flush')->never();
		$this->setExpectedException(InvalidArgumentException::class);

		$this->runCommand($command, ['store' => 'bar']);
	}


	protected function runCommand($command, $input = array())
	{
		return $command->run(new ArrayInput($input), new NullOutput);
	}

}

class ClearCommandTestStub extends ClearCommand {

	public function call($command, array $arguments = array())
	{
		//
	}

}
