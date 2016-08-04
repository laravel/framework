<?php

use Mockery as m;
use Illuminate\Foundation\Application;
use Illuminate\Config\Repository as Config;
use Illuminate\Cache\Console\CacheTableCommand;

class CacheTableCommandTest extends PHPUnit_Framework_TestCase
{
    public function tearDown()
    {
        m::close();
    }

    public function testCreateMakesMigration()
    {
        $command = new CacheTableCommandTestStub(
            $files = m::mock('Illuminate\Filesystem\Filesystem'),
            $composer = m::mock('Illuminate\Support\Composer')
        );
        $creator = m::mock('Illuminate\Database\Migrations\MigrationCreator')->shouldIgnoreMissing();

        $app = new Application();
        $app->singleton('config', function () {
            return $this->createConfig();
        });

        $app->useDatabasePath(__DIR__);
        $app['migration.creator'] = $creator;
        $command->setLaravel($app);
        $path = __DIR__.'/migrations';
        $creator->shouldReceive('create')->once()->with('create_cache_test_table', $path)->andReturn($path);
        $files->shouldReceive('get')->once()->andReturn('foo');
        $files->shouldReceive('put')->once()->with($path, 'foo');
        $composer->shouldReceive('dumpAutoloads')->once();

        $this->runCommand($command);
    }

    protected function runCommand($command, $input = [])
    {
        return $command->run(new Symfony\Component\Console\Input\ArrayInput($input), new Symfony\Component\Console\Output\NullOutput);
    }

    /**
     * Create a new config repository instance.
     *
     * @return \Illuminate\Config\Repository
     */
    protected function createConfig()
    {
        return new Config([
            'cache' => [
                'stores' => [
                    'database' => ['table' => 'cache_test'],
                ],
            ],
        ]);
    }
}

class CacheTableCommandTestStub extends CacheTableCommand
{
    public function call($command, array $arguments = [])
    {
        //
    }
}
