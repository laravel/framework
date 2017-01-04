<?php

namespace Illuminate\Tests\Database;

use Mockery as m;
use PHPUnit\Framework\TestCase;

class DatabaseMigrationInstallCommandTest extends TestCase
{
    public function tearDown()
    {
        m::close();
    }

    public function testFireCallsRepositoryToInstall()
    {
        $command = new \Illuminate\Database\Console\Migrations\InstallCommand($repo = m::mock('Illuminate\Database\Migrations\MigrationRepositoryInterface'));
        $command->setLaravel(new \Illuminate\Foundation\Application);
        $repo->shouldReceive('setSource')->once()->with('foo');
        $repo->shouldReceive('createRepository')->once();

        $this->runCommand($command, ['--database' => 'foo']);
    }

    protected function runCommand($command, $options = [])
    {
        return $command->run(new \Symfony\Component\Console\Input\ArrayInput($options), new \Symfony\Component\Console\Output\NullOutput);
    }
}
