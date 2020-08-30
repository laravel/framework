<?php

namespace Illuminate\Tests\Session;

use Illuminate\Database\Migrations\MigrationCreator;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Application;
use Illuminate\Session\Console\SessionTableCommand;
use Illuminate\Support\Composer;
use Mockery as m;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;

class SessionTableCommandTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
    }

    public function testCreateMakesMigration()
    {
        $command = new SessionTableCommandTestStub(
            $files = m::mock(Filesystem::class),
            $composer = m::mock(Composer::class)
        );
        $creator = m::mock(MigrationCreator::class)->shouldIgnoreMissing();

        $app = new Application;
        $app->useDatabasePath(__DIR__);
        $app['migration.creator'] = $creator;
        $command->setLaravel($app);
        $path = __DIR__.'/migrations';
        $creator->shouldReceive('create')->once()->with('create_sessions_table', $path)->andReturn($path);
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

class SessionTableCommandTestStub extends SessionTableCommand
{
    public function call($command, array $arguments = [])
    {
        return 0;
    }
}
