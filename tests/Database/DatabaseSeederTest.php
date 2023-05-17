<?php

namespace Illuminate\Tests\Database;

use Illuminate\Console\Command;
use Illuminate\Container\Container;
use Illuminate\Database\Seeder;
use Mockery as m;
use Mockery\Mock;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Output\OutputInterface;

class TestSeeder extends Seeder
{
    public function run()
    {
        //
    }
}

class TestDepsSeeder extends Seeder
{
    public function run(Mock $someDependency, $someParam = '')
    {
        //
    }
}

class DatabaseSeederTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
    }

    public function testCallResolveTheClassAndCallsRun()
    {
        $seeder = new TestSeeder;
        $seeder->setContainer($container = m::mock(Container::class));
        $output = m::mock(OutputInterface::class);
        $output->shouldReceive('writeln')->times(3);
        $command = m::mock(Command::class);
        $command->shouldReceive('getOutput')->times(3)->andReturn($output);
        $seeder->setCommand($command);
        $container->shouldReceive('make')->once()->with('ClassName')->andReturn($child = m::mock(Seeder::class));
        $child->shouldReceive('setContainer')->once()->with($container)->andReturn($child);
        $child->shouldReceive('setCommand')->once()->with($command)->andReturn($child);
        $child->shouldReceive('__invoke')->once();

        $seeder->call('ClassName');
    }

    public function testSetContainer()
    {
        $seeder = new TestSeeder;
        $container = m::mock(Container::class);
        $this->assertEquals($seeder->setContainer($container), $seeder);
    }

    public function testSetCommand()
    {
        $seeder = new TestSeeder;
        $command = m::mock(Command::class);
        $this->assertEquals($seeder->setCommand($command), $seeder);
    }

    public function testInjectDependenciesOnRunMethod()
    {
        $container = m::mock(Container::class);
        $container->shouldReceive('call');

        $seeder = new TestDepsSeeder;
        $seeder->setContainer($container);

        $seeder->__invoke();

        $container->shouldHaveReceived('call')->once()->with([$seeder, 'run'], []);
    }

    public function testSendParamsOnCallMethodWithDeps()
    {
        $container = m::mock(Container::class);
        $container->shouldReceive('call');

        $seeder = new TestDepsSeeder;
        $seeder->setContainer($container);

        $seeder->__invoke(['test1', 'test2']);

        $container->shouldHaveReceived('call')->once()->with([$seeder, 'run'], ['test1', 'test2']);
    }
}
