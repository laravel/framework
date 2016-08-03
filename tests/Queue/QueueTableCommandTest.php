<?php

use Mockery as m;
use Illuminate\Foundation\Application;
use Illuminate\Queue\Console\TableCommand;
use Illuminate\Config\Repository as Config;
use Illuminate\Queue\Console\FailedTableCommand;

class QueueTableCommandTest extends PHPUnit_Framework_TestCase
{
    public function tearDown()
    {
        m::close();
    }

    public function setUp()
    {
        $this->app = new Application();
        $this->app->singleton('config', function () {
            return $this->createConfig();
        });

        $this->app->useDatabasePath(__DIR__);

        $this->creator = m::mock('Illuminate\Database\Migrations\MigrationCreator')->shouldIgnoreMissing();
        $this->app['migration.creator'] = $this->creator;
    }

    public function testCreateMakesMigration()
    {
        $command = new QueueTableCommandTestStub(
            $files = m::mock('Illuminate\Filesystem\Filesystem'),
            $composer = m::mock('Illuminate\Support\Composer')
        );

        $command->setLaravel($this->app);
        $path = __DIR__.'/migrations';
        $this->creator->shouldReceive('create')->once()->with('create_jobs_test_table', $path)->andReturn($path);
        $files->shouldReceive('get')->once()->andReturn('foo');
        $files->shouldReceive('put')->once()->with($path, 'foo');
        $composer->shouldReceive('dumpAutoloads')->once();

        $this->runCommand($command);
    }

    public function testCreateMakesFailedMigration()
    {
        $command = new QueueFailedTableCommandTestStub(
            $files = m::mock('Illuminate\Filesystem\Filesystem'),
            $composer = m::mock('Illuminate\Support\Composer')
        );

        $command->setLaravel($this->app);
        $path = __DIR__.'/migrations';
        $this->creator->shouldReceive('create')->once()->with('create_failed_test_table', $path)->andReturn($path);
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
            'queue' => [
                'connections' => [
                    'database' => ['table' => 'jobs_test'],
                ],
                'failed' => [
                    'table' => 'failed_test'
                ]
            ],
        ]);
    }
}

class QueueTableCommandTestStub extends TableCommand
{
    public function call($command, array $arguments = [])
    {
        //
    }
}

class QueueFailedTableCommandTestStub extends FailedTableCommand
{
    public function call($command, array $arguments = [])
    {
        //
    }
}
