<?php

namespace Illuminate\Tests\Cache;

use Mockery as m;
use PHPUnit\Framework\TestCase;
use Illuminate\Support\Composer;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Application;
use Illuminate\Cache\Console\CacheTableCommand;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;
use Illuminate\Database\Migrations\MigrationCreator;

class CacheTableCommandTest extends TestCase
{
    public function tearDown(): void
    {
        m::close();
    }

    public function testCreateMakesMigration()
    {
        $command = new CacheTableCommandTestStub(
            $files = m::mock(Filesystem::class),
            $composer = m::mock(Composer::class)
        );
        $creator = m::mock(MigrationCreator::class)->shouldIgnoreMissing();

        $app = new Application;
        $app->useDatabasePath(__DIR__);
        $app['migration.creator'] = $creator;
        $command->setLaravel($app);
        $path = __DIR__.'/migrations';
        $creator->shouldReceive('create')->once()->with('create_cache_table', $path)->andReturn($path);
        $files->shouldReceive('get')->once()->andReturn('foo');
        $files->shouldReceive('put')->once()->with($path, 'foo');
        $composer->shouldReceive('dumpAutoloads')->once();

        $this->runCommand($command);
    }

    protected function runCommand($command, $input = [])
    {
        return $command->run(new ArrayInput($input), new NullOutput);
    }
}

class CacheTableCommandTestStub extends CacheTableCommand
{
    public function call($command, array $arguments = [])
    {
        //
    }
}
