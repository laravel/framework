<?php

namespace Illuminate\Tests\Database;

use Illuminate\Database\Console\Migrations\InstallCommand;
use Illuminate\Database\Migrations\MigrationRepositoryInterface;
use Illuminate\Foundation\Application;
use Mockery;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;

class DatabaseMigrationInstallCommandTest extends TestCase
{
    public function testFireCallsRepositoryToInstall()
    {
        $repo = Mockery::mock(MigrationRepositoryInterface::class);
        $command = new InstallCommand($repo);
        $command->setLaravel(new Application);
        $repo->expects('setSource')->with('foo');
        $repo->expects('createRepository');
        $repo->expects('repositoryExists')->andReturn(false);

        $this->runCommand($command, ['--database' => 'foo']);
    }

    public function testFireCallsRepositoryToInstallExists()
    {
        $repo = Mockery::mock(MigrationRepositoryInterface::class);
        $command = new InstallCommand($repo);
        $command->setLaravel(new Application);
        $repo->expects('setSource')->with('foo');
        $repo->expects('repositoryExists')->andReturn(true);

        $this->runCommand($command, ['--database' => 'foo']);
    }

    protected function runCommand($command, $options = [])
    {
        return $command->run(new ArrayInput($options), new NullOutput);
    }
}
