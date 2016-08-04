<?php

use Mockery as m;
use Illuminate\Foundation\Application;
use Illuminate\Config\Repository as Config;
use Illuminate\Session\Console\SessionTableCommand;

class SessionTableCommandTest extends PHPUnit_Framework_TestCase
{
    public function tearDown()
    {
        m::close();
    }

    public function testCreateMakesMigration()
    {
        $command = new SessionTableCommandTestStub(
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
        $creator->shouldReceive('create')->once()->with('create_sessions_test_table', $path)->andReturn($path);
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
            'session' => [
                'table' => 'sessions_test',
            ],
        ]);
    }
}

class SessionTableCommandTestStub extends SessionTableCommand
{
    public function call($command, array $arguments = [])
    {
        //
    }
}
