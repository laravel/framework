<?php

namespace Illuminate\Tests\Database;

use Mockery;
use Illuminate\Database\Seeder;
use PHPUnit\Framework\TestCase;
use Illuminate\Console\OutputStyle;
use Illuminate\Container\Container;
use Illuminate\Database\Console\Seeds\SeedCommand;
use Illuminate\Database\ConnectionResolverInterface;

class SeedCommandTest extends TestCase
{
    public function testHandle()
    {
        $input = new \Symfony\Component\Console\Input\ArrayInput(['--force' => true, '--database' => 'sqlite']);
        $output = new \Symfony\Component\Console\Output\NullOutput;

        $seeder = Mockery::mock(Seeder::class);
        $seeder->shouldReceive('setContainer')->once()->andReturnSelf();
        $seeder->shouldReceive('setCommand')->once()->andReturnSelf();
        $seeder->shouldReceive('__invoke')->once();

        $resolver = Mockery::mock(ConnectionResolverInterface::class);
        $resolver->shouldReceive('setDefaultConnection')->once()->with('sqlite');

        $container = Mockery::mock(Container::class);
        $container->shouldReceive('call');
        $container->shouldReceive('environment')->once()->andReturn('testing');
        $container->shouldReceive('make')->with('DatabaseSeeder')->andReturn($seeder);
        $container->shouldReceive('make')->with('Illuminate\Console\OutputStyle', Mockery::any())->andReturn(
            new OutputStyle($input, $output)
        );

        $command = new SeedCommand($resolver);
        $command->setLaravel($container);

        // call run to set up IO, then fire manually.
        $command->run($input, $output);
        $command->handle();

        $container->shouldHaveReceived('call')->with([$command, 'handle']);
    }

    protected function tearDown()
    {
        Mockery::close();
    }
}
