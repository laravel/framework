<?php

use Mockery as m;
use Illuminate\Database\Seeder;

class TestSeeder extends Seeder
{
    public function run()
    {
        //
    }
}

class DatabaseSeederTest extends PHPUnit_Framework_TestCase
{
    public function tearDown()
    {
        m::close();
    }

    public function testCallResolveTheClassAndCallsRun()
    {
        $seeder = new TestSeeder;
        $seeder->setContainer($container = m::mock('Illuminate\Container\Container'));
        $output = m::mock('Symfony\Component\Console\Output\OutputInterface');
        $output->shouldReceive('writeln')->once()->andReturn('foo');
        $command = m::mock('Illuminate\Console\Command');
        $command->shouldReceive('getOutput')->once()->andReturn($output);
        $seeder->setCommand($command);
        $child = m::mock('StdClass');
        $child->dependencies = [];
        $container->shouldReceive('make')->once()->with('ClassName')->andReturn($child);
        $child->shouldReceive('setContainer')->once()->with($container)->andReturn($child);
        $child->shouldReceive('setCommand')->once()->with($command)->andReturn($child);
        $child->shouldReceive('run')->once();

        $seeder->call('ClassName');
    }

    public function testCallResolveDependencies()
    {
        $container = m::mock('Illuminate\Container\Container');

        $seeder = new TestSeeder;
        $seeder->setContainer($container);

        $output = m::mock('Symfony\Component\Console\Output\OutputInterface');
        $output->shouldReceive('writeln')->twice()->andReturn('foo');

        $command = m::mock('Illuminate\Console\Command');
        $command->shouldReceive('getOutput')->twice()->andReturn($output);
        $seeder->setCommand($command);

        $child = m::mock('StdClass');
        $child->dependencies = ['ClassTwo'];

        $dependant = m::mock('StdClass');
        $dependant->dependencies = [];

        $container->shouldReceive('make')->once()->with('ClassName')->andReturn($child);
        $container->shouldReceive('make')->once()->with('ClassTwo')->andReturn($dependant);

        $child->shouldReceive('setContainer')->once()->with($container)->andReturn($child);
        $child->shouldReceive('setCommand')->once()->with($command)->andReturn($child);
        $child->shouldReceive('run')->once();

        $dependant->shouldReceive('setContainer')->once()->with($container)->andReturn($dependant);
        $dependant->shouldReceive('setCommand')->once()->with($command)->andReturn($dependant);
        $dependant->shouldReceive('run')->once();

        $seeder->call('ClassName');
    }

    public function testSetContainer()
    {
        $seeder = new TestSeeder;
        $container = m::mock('Illuminate\Container\Container');
        $this->assertEquals($seeder->setContainer($container), $seeder);
    }

    public function testSetCommand()
    {
        $seeder = new TestSeeder;
        $command = m::mock('Illuminate\Console\Command');
        $this->assertEquals($seeder->setCommand($command), $seeder);
    }
}
