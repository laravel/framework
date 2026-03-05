<?php

namespace Illuminate\Tests\Database;

use Closure;
use Illuminate\Database\Console\Migrations\FreshCommand;
use Illuminate\Database\Migrations\Migrator;
use Illuminate\Foundation\Application;
use Mockery as m;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;

class DatabaseMigrationFreshCommandTest extends TestCase
{
    protected function tearDown(): void
    {
        FreshCommand::prohibit(false);

        parent::tearDown();
    }

    public function testFreshCommandMayForwardWithOptionsToSeeder()
    {
        $params = [$migrator = m::mock(Migrator::class)];
        $command = $this->getMockBuilder(FreshCommand::class)->onlyMethods(['call', 'callSilent'])->setConstructorArgs($params)->getMock();
        $app = new ApplicationDatabaseFreshStub(['path.database' => __DIR__]);
        $command->setLaravel($app);

        $migrator->shouldReceive('usingConnection')->once()->with(null, m::type(Closure::class))->andReturnUsing(function ($connection, $callback) {
            $callback();
        });
        $migrator->shouldReceive('repositoryExists')->once()->andReturn(false);

        $calls = [];

        $command->method('call')->willReturnCallback(function ($commandName, array $arguments = []) use (&$calls) {
            $calls[$commandName][] = $arguments;

            return 0;
        });
        $command->expects($this->never())->method('callSilent');

        $this->runCommand($command, ['--seed' => true, '--with' => ['count=10', 'active=true']]);

        $this->assertSame([
            '--class' => 'Database\\Seeders\\DatabaseSeeder',
            '--force' => true,
            '--with' => ['count=10', 'active=true'],
        ], $calls['db:seed'][0] ?? null);
    }

    protected function runCommand($command, $input = [])
    {
        return $command->run(new ArrayInput($input), new NullOutput);
    }
}

class ApplicationDatabaseFreshStub extends Application
{
    public function __construct(array $data = [])
    {
        foreach ($data as $abstract => $instance) {
            $this->instance($abstract, $instance);
        }
    }

    public function environment(...$environments)
    {
        return 'development';
    }
}
