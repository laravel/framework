<?php

namespace Illuminate\Tests\Database;

use Illuminate\Console\Command;
use Illuminate\Console\OutputStyle;
use Illuminate\Console\View\Components\Factory;
use Illuminate\Container\Container;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Database\Console\Seeds\SeedCommand;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Illuminate\Events\NullDispatcher;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Testing\Assert;
use InvalidArgumentException;
use Mockery as m;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;

class SeedCommandTest extends TestCase
{
    public function testHandle()
    {
        $input = new ArrayInput(['--force' => true, '--database' => 'sqlite']);
        $output = new NullOutput;
        $outputStyle = new OutputStyle($input, $output);

        $seeder = m::mock(Seeder::class);
        $seeder->shouldReceive('setContainer')->once()->andReturnSelf();
        $seeder->shouldReceive('setCommand')->once()->andReturnSelf();
        $seeder->shouldReceive('__invoke')->once();
        $seeder->shouldReceive('useTransactions')->andReturnTrue();

        $resolver = m::mock(ConnectionResolverInterface::class);
        $resolver->shouldReceive('getDefaultConnection')->once();
        $resolver->shouldReceive('setDefaultConnection')->once()->with('sqlite');

        $container = m::mock(Container::class);
        $container->shouldReceive('call');
        $container->shouldReceive('environment')->once()->andReturn('testing');
        $container->shouldReceive('runningUnitTests')->andReturn('true');
        $container->shouldReceive('make')->with('DatabaseSeeder')->andReturn($seeder);
        $container->shouldReceive('make')->with(OutputStyle::class, m::any())->andReturn($outputStyle);
        $container->shouldReceive('make')->with(Factory::class, m::any())->andReturn(new Factory($outputStyle));
        $container->shouldReceive('storagePath')->andReturn('test_path');
        $container->shouldReceive('bound')->with(Filesystem::class)->andReturn(false);

        $command = new SeedCommand($resolver);
        $command->setLaravel($container);

        // call run to set up IO, then fire manually.
        $command->run($input, $output);
        $command->handle();

        $container->shouldHaveReceived('call')->with([$command, 'handle']);
    }

    public function testWithoutModelEvents()
    {
        $input = new ArrayInput([
            '--force' => true,
            '--database' => 'sqlite',
            '--class' => UserWithoutModelEventsSeeder::class,
        ]);
        $output = new NullOutput;
        $outputStyle = new OutputStyle($input, $output);

        $instance = new UserWithoutModelEventsSeeder();

        $seeder = m::mock($instance);
        $seeder->shouldReceive('setContainer')->once()->andReturnSelf();
        $seeder->shouldReceive('setCommand')->once()->andReturnSelf();

        $resolver = m::mock(ConnectionResolverInterface::class);
        $resolver->shouldReceive('getDefaultConnection')->once();
        $resolver->shouldReceive('setDefaultConnection')->once()->with('sqlite');

        $container = m::mock(Container::class);
        $container->shouldReceive('call');
        $container->shouldReceive('environment')->once()->andReturn('testing');
        $container->shouldReceive('runningUnitTests')->andReturn('true');
        $container->shouldReceive('make')->with(UserWithoutModelEventsSeeder::class)->andReturn($seeder);
        $container->shouldReceive('make')->with(OutputStyle::class, m::any())->andReturn($outputStyle);
        $container->shouldReceive('make')->with(Factory::class, m::any())->andReturn(new Factory($outputStyle));
        $container->shouldReceive('storagePath')->andReturn('test_path');
        $container->shouldReceive('bound')->with(Filesystem::class)->andReturn(false);

        $command = new SeedCommand($resolver);
        $command->setLaravel($container);

        Model::setEventDispatcher($dispatcher = m::mock(Dispatcher::class));

        // call run to set up IO, then fire manually.
        $command->run($input, $output);
        $command->handle();

        Assert::assertSame($dispatcher, Model::getEventDispatcher());

        $container->shouldHaveReceived('call')->with([$command, 'handle']);
    }

    public function testProhibitable()
    {
        $input = new ArrayInput([]);
        $output = new NullOutput;
        $outputStyle = new OutputStyle($input, $output);

        $resolver = m::mock(ConnectionResolverInterface::class);

        $container = m::mock(Container::class);
        $container->shouldReceive('call');
        $container->shouldReceive('runningUnitTests')->andReturn('true');
        $container->shouldReceive('make')->with(OutputStyle::class, m::any())->andReturn($outputStyle);
        $container->shouldReceive('make')->with(Factory::class, m::any())->andReturn(new Factory($outputStyle));
        $container->shouldReceive('bound')->with(Filesystem::class)->andReturn(false);

        $command = new SeedCommand($resolver);
        $command->setLaravel($container);

        // call run to set up IO, then fire manually.
        $command->run($input, $output);

        SeedCommand::prohibit();

        Assert::assertSame(Command::FAILURE, $command->handle());
    }

    public function testContinueableThrowsWithoutFileSystem()
    {
        $input = new ArrayInput(['--continue' => true, '--database' => 'sqlite']);
        $output = new NullOutput;
        $outputStyle = new OutputStyle($input, $output);

        $seeder = m::mock(Seeder::class);
        $seeder->shouldReceive('setContainer')->once()->andReturnSelf();
        $seeder->shouldReceive('setCommand')->once()->andReturnSelf();
        $seeder->shouldReceive('__invoke')->never();
        $seeder->shouldReceive('useTransactions')->andReturnTrue();

        $resolver = m::mock(ConnectionResolverInterface::class);
        $resolver->shouldReceive('getDefaultConnection')->once();
        $resolver->shouldReceive('setDefaultConnection')->once()->with('sqlite');

        $container = m::mock(Container::class);
        $container->shouldReceive('call');
        $container->shouldReceive('environment')->once()->andReturn('testing');
        $container->shouldReceive('runningUnitTests')->andReturn('true');
        $container->shouldReceive('make')->with('DatabaseSeeder')->andReturn($seeder);
        $container->shouldReceive('make')->with(OutputStyle::class, m::any())->andReturn($outputStyle);
        $container->shouldReceive('make')->with(Factory::class, m::any())->andReturn(new Factory($outputStyle));
        $container->shouldReceive('storagePath')->andReturn('test_path');
        $container->shouldReceive('bound')->with(Filesystem::class)->andReturn(false);

        $command = new SeedCommand($resolver);
        $command->setLaravel($container);

        // call run to set up IO, then fire manually.
        $command->run($input, $output);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unable to retrieve continue data. Install the "illuminate/filesystem" package to use the --continue option.');

        Assert::assertSame(Command::FAILURE, $command->handle());
    }

    public function testContinueable()
    {
        $input = new ArrayInput(['--continue' => true, '--database' => 'sqlite']);
        $output = new NullOutput;
        $outputStyle = new OutputStyle($input, $output);

        $seeder = m::mock(Seeder::class);
        $seeder->shouldReceive('setContainer')->once()->andReturnSelf();
        $seeder->shouldReceive('setCommand')->once()->andReturnSelf();
        $seeder->shouldReceive('__invoke')->once();
        $seeder->shouldReceive('useTransactions')->andReturnTrue();
        $seeder->shouldReceive('setContinue')->with(['test' => 'class']);

        $resolver = m::mock(ConnectionResolverInterface::class);
        $resolver->shouldReceive('getDefaultConnection')->once();
        $resolver->shouldReceive('setDefaultConnection')->once()->with('sqlite');

        $filesystem = m::mock(Filesystem::class);
        $filesystem->shouldReceive('exists')->with('test_path')->andReturn(true);
        $filesystem->shouldReceive('json')->with('test_path')->andReturn(['test' => 'class']);
        $filesystem->shouldReceive('delete')->with('test_path');

        $container = m::mock(Container::class);
        $container->shouldReceive('call');
        $container->shouldReceive('environment')->once()->andReturn('testing');
        $container->shouldReceive('runningUnitTests')->andReturn('true');
        $container->shouldReceive('make')->with('DatabaseSeeder')->andReturn($seeder);
        $container->shouldReceive('make')->with(OutputStyle::class, m::any())->andReturn($outputStyle);
        $container->shouldReceive('make')->with(Factory::class, m::any())->andReturn(new Factory($outputStyle));
        $container->shouldReceive('storagePath')->andReturn('test_path');
        $container->shouldReceive('bound')->with(Filesystem::class)->andReturn(true);
        $container->shouldReceive('make')->with(Filesystem::class)->andReturn($filesystem);

        $command = new SeedCommand($resolver);
        $command->setLaravel($container);

        // call run to set up IO, then fire manually.
        $command->run($input, $output);
        $command->handle();

        $container->shouldHaveReceived('call')->with([$command, 'handle']);
    }

    public function testStoresIncompleteSeedData()
    {
        $input = new ArrayInput(['--database' => 'sqlite']);
        $output = new NullOutput;
        $outputStyle = new OutputStyle($input, $output);

        $seeder = m::mock(Seeder::class);
        $seeder->shouldReceive('setContainer')->once()->andReturnSelf();
        $seeder->shouldReceive('setCommand')->once()->andReturnSelf();
        $seeder->shouldReceive('__invoke')->once()->andThrow(new RuntimeException());
        $seeder->shouldReceive('useTransactions')->andReturnTrue();
        $seeder->shouldReceive('getContinue')->andReturn(['test' => 'class']);

        $resolver = m::mock(ConnectionResolverInterface::class);
        $resolver->shouldReceive('getDefaultConnection')->once();
        $resolver->shouldReceive('setDefaultConnection')->once()->with('sqlite');

        $filesystem = m::mock(Filesystem::class);
        $filesystem->shouldReceive('ensureDirectoryExists')->with('test_path')->andReturn(true);
        $filesystem->shouldReceive('put')->with('test_path', ['test' => 'class'])->andReturn(true);
        $filesystem->shouldReceive('delete')->never();

        $container = m::mock(Container::class);
        $container->shouldReceive('call');
        $container->shouldReceive('environment')->once()->andReturn('testing');
        $container->shouldReceive('runningUnitTests')->andReturn('true');
        $container->shouldReceive('make')->with('DatabaseSeeder')->andReturn($seeder);
        $container->shouldReceive('make')->with(OutputStyle::class, m::any())->andReturn($outputStyle);
        $container->shouldReceive('make')->with(Factory::class, m::any())->andReturn(new Factory($outputStyle));
        $container->shouldReceive('storagePath')->andReturn('test_path');
        $container->shouldReceive('bound')->with(Filesystem::class)->andReturn(true);
        $container->shouldReceive('make')->with(Filesystem::class)->andReturn($filesystem);

        $command = new SeedCommand($resolver);
        $command->setLaravel($container);

        // call run to set up IO, then fire manually.
        $command->run($input, $output);

        $this->expectException(RuntimeException::class);

        $command->handle();
    }

    protected function tearDown(): void
    {
        SeedCommand::prohibit(false);

        Model::unsetEventDispatcher();

        m::close();
    }
}

class UserWithoutModelEventsSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run()
    {
        Assert::assertInstanceOf(NullDispatcher::class, Model::getEventDispatcher());
    }
}
