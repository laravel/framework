<?php

namespace Illuminate\Tests\Database;

use Mockery as m;
use Mockery\Mock;
use Illuminate\Console\Command;
use Illuminate\Database\Seeder;
use PHPUnit\Framework\TestCase;
use Illuminate\Container\Container;
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
    public function run(Mock $someDependency)
    {
        //
    }
}

class DatabaseSeederTest extends TestCase
{
    public function tearDown(): void
    {
        m::close();
    }

    public function testCallResolveTheClassAndCallsRun()
    {
        $seeder = new TestSeeder;
        $seeder->setContainer($container = m::mock(Container::class));
        $output = m::mock(OutputInterface::class);
        $output->shouldReceive('writeln')->once()->andReturn('foo');
        $command = m::mock(Command::class);
        $command->shouldReceive('getOutput')->once()->andReturn($output);
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

        $container->shouldHaveReceived('call')->once()->with([$seeder, 'run']);
    }
}
