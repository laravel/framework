<?php

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
        $memcached->shouldReceive('fetchAll')->once();

        $arguments = ['store' => 'memcached'];
        $command->run(new ArrayInput($arguments), new NullOutput);
    }
}

class ShowCommandTester extends ShowCommand
{

}