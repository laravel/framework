<?php

use Predis\ClientInterface;
use Illuminate\Cache\CacheManager;
use Illuminate\Contracts\Cache\Store;
use Illuminate\Foundation\Application;
use Illuminate\Cache\Console\ShowCommand;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;

class ShowCommandTest extends PHPUnit_Framework_TestCase
{
    public function testShowMemcachedCommand()
    {
        $command = new ShowCommandTester(
            $cacheManager = Mockery::mock(CacheManager::class)
        );

        $memcached = Mockery::mock(Memcached::class);
        $cacheStore = Mockery::mock(Store::class);

        $application = new Application;
        $command->setLaravel($application);

        $cacheManager->shouldReceive('store')->once()->with('memcached')->andReturn($cacheStore);
        $cacheStore->shouldReceive('getMemcached')->once()->andReturn($memcached);
        $memcached->shouldReceive('getAllKeys')->once();

        $arguments = ['store' => 'memcached'];
        $command->run(new ArrayInput($arguments), new NullOutput);
    }

    public function testShowRedisCommand()
    {
        $command = new ShowCommandTester(
            $cacheManager = Mockery::mock(CacheManager::class)
        );

        $cacheStore = Mockery::mock(Store::class);
        $predisMock = Mockery::mock(ClientInterface::class);

        $application = new Application;
        $command->setLaravel($application);

        $cacheManager->shouldReceive('store')->once()->with('redis')->andReturn($cacheStore);
        $cacheStore->shouldReceive('connection')->once()->andReturn($predisMock);
        $predisMock->shouldReceive('executeRaw')->once()->withArgs([['keys', '*']]);

        $arguments = ['store' => 'redis'];
        $command->run(new ArrayInput($arguments), new NullOutput);
    }

    public function testExpectedOutputWithInvalidStore()
    {
        $command = new ShowCommandTester(
            $cacheManager = Mockery::mock(CacheManager::class)
        );

        $cacheStore = Mockery::mock(Store::class);

        $application = new Application;
        $command->setLaravel($application);

        $cacheManager->shouldReceive('store')->once()->with('invalid')->andReturn(InvalidArgumentException::class);

        $arguments = ['store' => 'invalid'];
        $command->run(new ArrayInput($arguments), new NullOutput());
    }
}

class ShowCommandTester extends ShowCommand
{
}