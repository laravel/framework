<?php

namespace Illuminate\Tests\Console;

use Illuminate\Console\Application;
use Illuminate\Console\Command;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Foundation\Application as ApplicationContract;
use Illuminate\Events\Dispatcher as EventsDispatcher;
use Illuminate\Foundation\Application as FoundationApplication;
use Illuminate\Tests\Console\Fixtures\FakeCommandWithArrayInputPrompting;
use Illuminate\Tests\Console\Fixtures\FakeCommandWithInputPrompting;
use Mockery as m;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Exception\CommandNotFoundException;
use Throwable;

class ConsoleApplicationTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
    }

    public function testAddSetsLaravelInstance()
    {
        $artisan = $this->getMockConsole(['addToParent']);
        $command = m::mock(Command::class);
        $command->shouldReceive('setLaravel')->once()->with(m::type(ApplicationContract::class));
        $artisan->expects($this->once())->method('addToParent')->with($this->equalTo($command))->willReturn($command);
        $result = $artisan->add($command);

        $this->assertSame($command, $result);
    }

    public function testLaravelNotSetOnSymfonyCommands()
    {
        $artisan = $this->getMockConsole(['addToParent']);
        $command = m::mock(SymfonyCommand::class);
        $command->shouldReceive('setLaravel')->never();
        $artisan->expects($this->once())->method('addToParent')->with($this->equalTo($command))->willReturn($command);
        $result = $artisan->add($command);

        $this->assertSame($command, $result);
    }

    public function testResolveAddsCommandViaApplicationResolution()
    {
        $artisan = $this->getMockConsole(['addToParent']);
        $command = m::mock(SymfonyCommand::class);
        $artisan->getLaravel()->shouldReceive('make')->once()->with('foo')->andReturn(m::mock(SymfonyCommand::class));
        $artisan->expects($this->once())->method('addToParent')->with($this->equalTo($command))->willReturn($command);
        $result = $artisan->resolve('foo');

        $this->assertSame($command, $result);
    }

    public function testResolvingCommandsWithAliasViaAttribute()
    {
        $container = new FoundationApplication();
        $artisan = new Application($container, new EventsDispatcher($container), $container->version());
        $artisan->resolve(CommandWithAliasViaAttribute::class);
        $artisan->setContainerCommandLoader();

        $this->assertInstanceOf(CommandWithAliasViaAttribute::class, $artisan->get('command-name'));
        $this->assertInstanceOf(CommandWithAliasViaAttribute::class, $artisan->get('command-alias'));
        $this->assertArrayHasKey('command-name', $artisan->all());
        $this->assertArrayHasKey('command-alias', $artisan->all());
    }

    public function testResolvingCommandsWithAliasViaProperty()
    {
        $container = new FoundationApplication();
        $artisan = new Application($container, new EventsDispatcher($container), $container->version());
        $artisan->resolve(CommandWithAliasViaProperty::class);
        $artisan->setContainerCommandLoader();

        $this->assertInstanceOf(CommandWithAliasViaProperty::class, $artisan->get('command-name'));
        $this->assertInstanceOf(CommandWithAliasViaProperty::class, $artisan->get('command-alias'));
        $this->assertArrayHasKey('command-name', $artisan->all());
        $this->assertArrayHasKey('command-alias', $artisan->all());
    }

    public function testResolvingCommandsWithNoAliasViaAttribute()
    {
        $container = new FoundationApplication();
        $artisan = new Application($container, new EventsDispatcher($container), $container->version());
        $artisan->resolve(CommandWithNoAliasViaAttribute::class);
        $artisan->setContainerCommandLoader();

        $this->assertInstanceOf(CommandWithNoAliasViaAttribute::class, $artisan->get('command-name'));
        try {
            $artisan->get('command-alias');
            $this->fail();
        } catch (Throwable $e) {
            $this->assertInstanceOf(CommandNotFoundException::class, $e);
        }
        $this->assertArrayHasKey('command-name', $artisan->all());
        $this->assertArrayNotHasKey('command-alias', $artisan->all());
    }

    public function testResolvingCommandsWithNoAliasViaProperty()
    {
        $container = new FoundationApplication();
        $artisan = new Application($container, new EventsDispatcher($container), $container->version());
        $artisan->resolve(CommandWithNoAliasViaProperty::class);
        $artisan->setContainerCommandLoader();

        $this->assertInstanceOf(CommandWithNoAliasViaProperty::class, $artisan->get('command-name'));
        try {
            $artisan->get('command-alias');
            $this->fail();
        } catch (Throwable $e) {
            $this->assertInstanceOf(CommandNotFoundException::class, $e);
        }
        $this->assertArrayHasKey('command-name', $artisan->all());
        $this->assertArrayNotHasKey('command-alias', $artisan->all());
    }

    public function testCallFullyStringCommandLine()
    {
        $artisan = new Application(
            m::mock(ApplicationContract::class, ['version' => '6.0']),
            m::mock(Dispatcher::class, ['dispatch' => null]),
            'testing'
        );

        $codeOfCallingArrayInput = $artisan->call('help', [
            '--raw' => true,
            '--format' => 'txt',
            '--no-interaction' => true,
            '--env' => 'testing',
        ]);

        $outputOfCallingArrayInput = $artisan->output();

        $codeOfCallingStringInput = $artisan->call(
            'help --raw --format=txt --no-interaction --env=testing'
        );

        $outputOfCallingStringInput = $artisan->output();

        $this->assertSame($codeOfCallingArrayInput, $codeOfCallingStringInput);
        $this->assertSame($outputOfCallingArrayInput, $outputOfCallingStringInput);
    }

    public function testCommandInputPromptsWhenRequiredArgumentIsMissing()
    {
        $artisan = new Application(
            $laravel = new \Illuminate\Foundation\Application(__DIR__),
            m::mock(Dispatcher::class, ['dispatch' => null]),
            'testing'
        );

        $artisan->addCommands([$command = new FakeCommandWithInputPrompting()]);

        $command->setLaravel($laravel);

        $exitCode = $artisan->call('fake-command-for-testing');

        $this->assertTrue($command->prompted);
        $this->assertSame('foo', $command->argument('name'));
        $this->assertSame(0, $exitCode);
    }

    public function testCommandInputDoesntPromptWhenRequiredArgumentIsPassed()
    {
        $artisan = new Application(
            new \Illuminate\Foundation\Application(__DIR__),
            m::mock(Dispatcher::class, ['dispatch' => null]),
            'testing'
        );

        $artisan->addCommands([$command = new FakeCommandWithInputPrompting()]);

        $exitCode = $artisan->call('fake-command-for-testing', [
            'name' => 'foo',
        ]);

        $this->assertFalse($command->prompted);
        $this->assertSame('foo', $command->argument('name'));
        $this->assertSame(0, $exitCode);
    }

    public function testCommandInputPromptsWhenRequiredArgumentsAreMissing()
    {
        $artisan = new Application(
            $laravel = new \Illuminate\Foundation\Application(__DIR__),
            m::mock(Dispatcher::class, ['dispatch' => null]),
            'testing'
        );

        $artisan->addCommands([$command = new FakeCommandWithArrayInputPrompting()]);

        $command->setLaravel($laravel);

        $exitCode = $artisan->call('fake-command-for-testing-array');

        $this->assertTrue($command->prompted);
        $this->assertSame(['foo'], $command->argument('names'));
        $this->assertSame(0, $exitCode);
    }

    public function testCommandInputDoesntPromptWhenRequiredArgumentsArePassed()
    {
        $artisan = new Application(
            new \Illuminate\Foundation\Application(__DIR__),
            m::mock(Dispatcher::class, ['dispatch' => null]),
            'testing'
        );

        $artisan->addCommands([$command = new FakeCommandWithArrayInputPrompting()]);

        $exitCode = $artisan->call('fake-command-for-testing-array', [
            'names' => ['foo', 'bar', 'baz'],
        ]);

        $this->assertFalse($command->prompted);
        $this->assertSame(['foo', 'bar', 'baz'], $command->argument('names'));
        $this->assertSame(0, $exitCode);
    }

    public function testCallMethodCanCallArtisanCommandUsingCommandClassObject()
    {
        $artisan = new Application(
            $laravel = new \Illuminate\Foundation\Application(__DIR__),
            m::mock(Dispatcher::class, ['dispatch' => null]),
            'testing'
        );

        $artisan->addCommands([$command = new FakeCommandWithInputPrompting()]);

        $command->setLaravel($laravel);

        $exitCode = $artisan->call($command);

        $this->assertTrue($command->prompted);
        $this->assertSame('foo', $command->argument('name'));
        $this->assertSame(0, $exitCode);
    }

    protected function getMockConsole(array $methods)
    {
        $app = m::mock(ApplicationContract::class, ['version' => '6.0']);
        $events = m::mock(Dispatcher::class, ['dispatch' => null]);

        return $this->getMockBuilder(Application::class)->onlyMethods($methods)->setConstructorArgs([
            $app, $events, 'test-version',
        ])->getMock();
    }
}

#[AsCommand('command-name')]
class CommandWithNoAliasViaAttribute extends Command
{
    //
}
#[AsCommand('command-name', aliases: ['command-alias'])]
class CommandWithAliasViaAttribute extends Command
{
    //
}

class CommandWithNoAliasViaProperty extends Command
{
    public $name = 'command-name';
}

class CommandWithAliasViaProperty extends Command
{
    public $name = 'command-name';
    public $aliases = ['command-alias'];
}
