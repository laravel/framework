<?php

namespace Illuminate\Tests\Database;

use Illuminate\Console\Command;
use Illuminate\Container\Container;
use Illuminate\Database\Seeder;
use Mockery;
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
    public function testCallResolveTheClassAndCallsRun()
    {
        $seeder = new TestSeeder;
        $container = Mockery::mock(Container::class);
        $seeder->setContainer($container);
        $output = Mockery::mock(OutputInterface::class);
        $output->expects('writeln')->times(3);
        $command = Mockery::mock(Command::class);
        $command->expects('getOutput')->times(3)->andReturn($output);
        $seeder->setCommand($command);
        $child = Mockery::mock(Seeder::class);
        $container->expects('make')->with('ClassName')->andReturn($child);
        $child->expects('setContainer')->with($container)->andReturn($child);
        $child->expects('setCommand')->with($command)->andReturn($child);
        $child->expects('__invoke');

        $seeder->call('ClassName');
    }

    public function testSetContainer()
    {
        $seeder = new TestSeeder;
        $container = Mockery::mock(Container::class);
        $this->assertEquals($seeder->setContainer($container), $seeder);
    }

    public function testSetCommand()
    {
        $seeder = new TestSeeder;
        $command = Mockery::mock(Command::class);
        $this->assertEquals($seeder->setCommand($command), $seeder);
    }

    public function testInjectDependenciesOnRunMethod()
    {
        $container = Mockery::mock(Container::class);
        $container->expects('call');

        $seeder = new TestDepsSeeder;
        $seeder->setContainer($container);

        $seeder->__invoke();

        $container->shouldHaveReceived('call')->once()->with([$seeder, 'run'], []);
    }

    public function testSendParamsOnCallMethodWithDeps()
    {
        $container = Mockery::mock(Container::class);
        $container->expects('call');

        $seeder = new TestDepsSeeder;
        $seeder->setContainer($container);

        $seeder->__invoke(['test1', 'test2']);

        $container->shouldHaveReceived('call')->once()->with([$seeder, 'run'], ['test1', 'test2']);
    }
}
