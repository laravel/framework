<?php

namespace Illuminate\Tests\Database;

use Illuminate\Database\Console\Migrations\InstallCommand;
use Illuminate\Database\Migrations\MigrationRepositoryInterface;
use Illuminate\Foundation\Application;
use Mockery as m;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;

class DatabaseMigrationInstallCommandTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
    }

    public function testFireCallsRepositoryToInstall()
    {
        $command = new InstallCommand($repo = m::mock(MigrationRepositoryInterface::class));
        $command->setLaravel(new Application);
        $repo->shouldReceive('setSource')->once()->with('foo');
        $repo->shouldReceive('createRepository')->once();
        $repo->shouldNotReceive('repositoryExists');

        $this->runCommand($command, ['--database' => 'foo']);
    }

    public function testFireCallsRepositoryToSkipInstallWhenExists()
    {
        $command = new InstallCommand($repo = m::mock(MigrationRepositoryInterface::class));
        $command->setLaravel(new Application);
        $repo->shouldReceive('setSource')->once()->with('bar');
        $repo->shouldReceive('repositoryExists')->once()->andReturn(true);
        $repo->shouldNotReceive('createRepository');

        $this->runCommand($command, ['--database' => 'bar', '--force' => false]);
    }

    public function testFireCallsRepositoryToInstallWhenForcing()
    {
        $command = new InstallCommand($repo = m::mock(MigrationRepositoryInterface::class));
        $command->setLaravel(new Application);
        $repo->shouldReceive('setSource')->once()->with('foo');
        $repo->shouldReceive('createRepository')->once();
        $repo->shouldNotReceive('repositoryExists');

        $this->runCommand($command, ['--database' => 'foo', '--force' => true]);
    }

    protected function runCommand($command, $options = [])
    {
        return $command->run(new ArrayInput($options), new NullOutput);
    }
}
