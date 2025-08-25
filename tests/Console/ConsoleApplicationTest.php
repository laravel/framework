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
        $app = $this->getMockConsole(['addToParent']);
        $command = m::mock(Command::class);
        $command->shouldReceive('setLaravel')->once()->with(m::type(ApplicationContract::class));
        $app->expects($this->once())->method('addToParent')->with($this->equalTo($command))->willReturn($command);
        $result = $app->add($command);

        $this->assertEquals($command, $result);
    }

    public function testLaravelNotSetOnSymfonyCommands()
    {
        $app = $this->getMockConsole(['addToParent']);
        $command = m::mock(SymfonyCommand::class);
        $command->shouldReceive('setLaravel')->never();
        $app->expects($this->once())->method('addToParent')->with($this->equalTo($command))->willReturn($command);
        $result = $app->add($command);

        $this->assertEquals($command, $result);
    }

    public function testResolveAddsCommandViaApplicationResolution()
    {
        $app = $this->getMockConsole(['addToParent']);
        $command = m::mock(SymfonyCommand::class);
        $app->getLaravel()->shouldReceive('make')->once()->with('foo')->andReturn(m::mock(SymfonyCommand::class));
        $app->expects($this->once())->method('addToParent')->with($this->equalTo($command))->willReturn($command);
        $result = $app->resolve('foo');

        $this->assertEquals($command, $result);
    }

    public function testResolvingCommandsWithAliasViaAttribute()
    {
        $container = new FoundationApplication();
        $app = new Application($container, new EventsDispatcher($container), $container->version());
        $app->resolve(CommandWithAliasViaAttribute::class);
        $app->setContainerCommandLoader();

        $this->assertInstanceOf(CommandWithAliasViaAttribute::class, $app->get('command-name'));
        $this->assertInstanceOf(CommandWithAliasViaAttribute::class, $app->get('command-alias'));
        $this->assertArrayHasKey('command-name', $app->all());
        $this->assertArrayHasKey('command-alias', $app->all());
    }

    public function testResolvingCommandsWithAliasViaProperty()
    {
        $container = new FoundationApplication();
        $app = new Application($container, new EventsDispatcher($container), $container->version());
        $app->resolve(CommandWithAliasViaProperty::class);
        $app->setContainerCommandLoader();

        $this->assertInstanceOf(CommandWithAliasViaProperty::class, $app->get('command-name'));
        $this->assertInstanceOf(CommandWithAliasViaProperty::class, $app->get('command-alias'));
        $this->assertArrayHasKey('command-name', $app->all());
        $this->assertArrayHasKey('command-alias', $app->all());
    }

    public function testResolvingCommandsWithNoAliasViaAttribute()
    {
        $container = new FoundationApplication();
        $app = new Application($container, new EventsDispatcher($container), $container->version());
        $app->resolve(CommandWithNoAliasViaAttribute::class);
        $app->setContainerCommandLoader();

        $this->assertInstanceOf(CommandWithNoAliasViaAttribute::class, $app->get('command-name'));
        try {
            $app->get('command-alias');
            $this->fail();
        } catch (Throwable $e) {
            $this->assertInstanceOf(CommandNotFoundException::class, $e);
        }
        $this->assertArrayHasKey('command-name', $app->all());
        $this->assertArrayNotHasKey('command-alias', $app->all());
    }

    public function testResolvingCommandsWithNoAliasViaProperty()
    {
        $container = new FoundationApplication();
        $app = new Application($container, new EventsDispatcher($container), $container->version());
        $app->resolve(CommandWithNoAliasViaProperty::class);
        $app->setContainerCommandLoader();

        $this->assertInstanceOf(CommandWithNoAliasViaProperty::class, $app->get('command-name'));
        try {
            $app->get('command-alias');
            $this->fail();
        } catch (Throwable $e) {
            $this->assertInstanceOf(CommandNotFoundException::class, $e);
        }
        $this->assertArrayHasKey('command-name', $app->all());
        $this->assertArrayNotHasKey('command-alias', $app->all());
    }

    public function testCallFullyStringCommandLine()
    {
        $app = new Application(
            $app = m::mock(ApplicationContract::class, ['version' => '6.0']),
            $events = m::mock(Dispatcher::class, ['dispatch' => null, 'fire' => null]),
            'testing'
        );

        $codeOfCallingArrayInput = $app->call('help', [
            '--raw' => true,
            '--format' => 'txt',
            '--no-interaction' => true,
            '--env' => 'testing',
        ]);

        $outputOfCallingArrayInput = $app->output();

        $codeOfCallingStringInput = $app->call(
            'help --raw --format=txt --no-interaction --env=testing'
        );

        $outputOfCallingStringInput = $app->output();

        $this->assertSame($codeOfCallingArrayInput, $codeOfCallingStringInput);
        $this->assertSame($outputOfCallingArrayInput, $outputOfCallingStringInput);
    }

    public function testCommandInputPromptsWhenRequiredArgumentIsMissing()
    {
        $app = new Application(
            $laravel = new \Illuminate\Foundation\Application(__DIR__),
            $events = m::mock(Dispatcher::class, ['dispatch' => null, 'fire' => null]),
            'testing'
        );

        $app->addCommands([$command = new FakeCommandWithInputPrompting()]);

        $command->setLaravel($laravel);

        $statusCode = $app->call('fake-command-for-testing');

        $this->assertTrue($command->prompted);
        $this->assertSame('foo', $command->argument('name'));
        $this->assertSame(0, $statusCode);
    }

    public function testCommandInputDoesntPromptWhenRequiredArgumentIsPassed()
    {
        $app = new Application(
            $app = new \Illuminate\Foundation\Application(__DIR__),
            $events = m::mock(Dispatcher::class, ['dispatch' => null, 'fire' => null]),
            'testing'
        );

        $app->addCommands([$command = new FakeCommandWithInputPrompting()]);

        $statusCode = $app->call('fake-command-for-testing', [
            'name' => 'foo',
        ]);

        $this->assertFalse($command->prompted);
        $this->assertSame('foo', $command->argument('name'));
        $this->assertSame(0, $statusCode);
    }

    public function testCommandInputPromptsWhenRequiredArgumentsAreMissing()
    {
        $app = new Application(
            $laravel = new \Illuminate\Foundation\Application(__DIR__),
            $events = m::mock(Dispatcher::class, ['dispatch' => null, 'fire' => null]),
            'testing'
        );

        $app->addCommands([$command = new FakeCommandWithArrayInputPrompting()]);

        $command->setLaravel($laravel);

        $statusCode = $app->call('fake-command-for-testing-array');

        $this->assertTrue($command->prompted);
        $this->assertSame(['foo'], $command->argument('names'));
        $this->assertSame(0, $statusCode);
    }

    public function testCommandInputDoesntPromptWhenRequiredArgumentsArePassed()
    {
        $app = new Application(
            $app = new \Illuminate\Foundation\Application(__DIR__),
            $events = m::mock(Dispatcher::class, ['dispatch' => null, 'fire' => null]),
            'testing'
        );

        $app->addCommands([$command = new FakeCommandWithArrayInputPrompting()]);

        $statusCode = $app->call('fake-command-for-testing-array', [
            'names' => ['foo', 'bar', 'baz'],
        ]);

        $this->assertFalse($command->prompted);
        $this->assertSame(['foo', 'bar', 'baz'], $command->argument('names'));
        $this->assertSame(0, $statusCode);
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
