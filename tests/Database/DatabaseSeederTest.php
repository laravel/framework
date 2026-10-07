<?php

namespace Illuminate\Tests\Database;

use JMac\Testing\Double;
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
        $container = Double::for(Container::class);
        $seeder->setContainer($container);
        $output = Double::for(OutputInterface::class);
        $output->expects('writeln')->times(3);
        $command = Double::for(Command::class);
        $command->expects('getOutput')->times(3)->returns($output);
        $seeder->setCommand($command);
        $child = Double::for(Seeder::class);
        $container->expects('make')->with('ClassName')->returns($child);
        $child->expects('setContainer')->with($container)->returns($child);
        $child->expects('setCommand')->with($command)->returns($child);
        $child->expects('__invoke');

        $seeder->call('ClassName');
    }

    public function testSetContainer()
    {
        $seeder = new TestSeeder;
        $container = new Container;
        $this->assertEquals($seeder->setContainer($container), $seeder);
    }

    public function testSetCommand()
    {
        $seeder = new TestSeeder;
        $command = new Command;
        $this->assertEquals($seeder->setCommand($command), $seeder);
    }

    public function testInjectDependenciesOnRunMethod()
    {
        $container = Double::for(Container::class);
        $container->expects('call');

        $seeder = new TestDepsSeeder;
        $seeder->setContainer($container);

        $seeder->__invoke();

        $container->received('call')->times(1)->with([$seeder, 'run'], []);
    }

    public function testSendParamsOnCallMethodWithDeps()
    {
        $container = Double::for(Container::class);
        $container->expects('call');

        $seeder = new TestDepsSeeder;
        $seeder->setContainer($container);

        $seeder->__invoke(['test1', 'test2']);

        $container->received('call')->times(1)->with([$seeder, 'run'], ['test1', 'test2']);
    }
}
