<?php

namespace Illuminate\Tests\Console;

use Illuminate\Console\Application;
use Illuminate\Console\Command;
use Illuminate\Events\Dispatcher as EventsDispatcher;
use Illuminate\Foundation\Application as FoundationApplication;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Exception\CommandNotFoundException;

class CommandOverridesTest extends TestCase
{
    protected function tearDown(): void
    {
        Command::flushState();
    }

    public function testReplacedCommandRunsTheReplacement()
    {
        Command::replace('vendor:make-user', 'user:make');

        $artisan = $this->getArtisan();
        $artisan->resolveCommands([VendorMakeUserCommand::class, UserMakeCommand::class]);

        $this->assertSame(0, $artisan->call('vendor:make-user'));
        $this->assertStringContainsString('user:make', $artisan->output());
    }

    public function testReplacedCommandIsRemovedFromTheCommandList()
    {
        Command::replace('vendor:make-user', 'user:make');

        $artisan = $this->getArtisan();
        $artisan->resolveCommands([VendorMakeUserCommand::class, UserMakeCommand::class]);

        $this->assertArrayNotHasKey('vendor:make-user', $artisan->all());
        $this->assertArrayHasKey('user:make', $artisan->all());
    }

    public function testReplacedCommandIsFoundAsTheReplacement()
    {
        Command::replace('vendor:make-user', 'user:make');

        $artisan = $this->getArtisan();
        $artisan->resolveCommands([VendorMakeUserCommand::class, UserMakeCommand::class]);

        $this->assertTrue($artisan->has('vendor:make-user'));
        $this->assertInstanceOf(UserMakeCommand::class, $artisan->get('vendor:make-user'));
        $this->assertInstanceOf(UserMakeCommand::class, $artisan->find('vendor:make-user'));
    }

    public function testLazilyRegisteredCommandsMayBeReplaced()
    {
        Command::replace('lazy:vendor-make-user', 'user:make');

        $artisan = $this->getArtisan();
        $artisan->resolveCommands([LazyVendorMakeUserCommand::class, UserMakeCommand::class]);
        $artisan->setContainerCommandLoader();

        $this->assertArrayNotHasKey('lazy:vendor-make-user', $artisan->all());
        $this->assertInstanceOf(UserMakeCommand::class, $artisan->find('lazy:vendor-make-user'));
    }

    public function testDisabledCommandIsNotRegistered()
    {
        Command::disable('vendor:make-user');

        $artisan = $this->getArtisan();
        $artisan->resolveCommands([VendorMakeUserCommand::class, UserMakeCommand::class]);

        $this->assertFalse($artisan->has('vendor:make-user'));
        $this->assertArrayNotHasKey('vendor:make-user', $artisan->all());
        $this->assertArrayHasKey('user:make', $artisan->all());
    }

    public function testDisabledCommandMayNotBeCalled()
    {
        Command::disable('vendor:make-user');

        $artisan = $this->getArtisan();
        $artisan->resolveCommands([VendorMakeUserCommand::class]);

        $this->expectException(CommandNotFoundException::class);

        $artisan->call('vendor:make-user');
    }

    public function testLazilyRegisteredCommandsMayBeDisabled()
    {
        Command::disable('lazy:vendor-make-user');

        $artisan = $this->getArtisan();
        $artisan->resolveCommands([LazyVendorMakeUserCommand::class]);
        $artisan->setContainerCommandLoader();

        $this->assertFalse($artisan->has('lazy:vendor-make-user'));
        $this->assertArrayNotHasKey('lazy:vendor-make-user', $artisan->all());

        $this->expectException(CommandNotFoundException::class);

        $artisan->find('lazy:vendor-make-user');
    }

    public function testMultipleCommandsMayBeDisabledAtOnce()
    {
        Command::disable('vendor:make-user', 'user:make');

        $artisan = $this->getArtisan();
        $artisan->resolveCommands([VendorMakeUserCommand::class, UserMakeCommand::class]);

        $this->assertArrayNotHasKey('vendor:make-user', $artisan->all());
        $this->assertArrayNotHasKey('user:make', $artisan->all());
    }

    public function testCommandsMayBeReplacedUsingClassNames()
    {
        Command::replace(VendorMakeUserCommand::class, UserMakeCommand::class);

        $artisan = $this->getArtisan();
        $artisan->resolveCommands([VendorMakeUserCommand::class, UserMakeCommand::class]);

        $this->assertArrayNotHasKey('vendor:make-user', $artisan->all());
        $this->assertInstanceOf(UserMakeCommand::class, $artisan->find('vendor:make-user'));
        $this->assertSame(0, $artisan->call('vendor:make-user'));
        $this->assertStringContainsString('user:make', $artisan->output());
    }

    public function testCommandNamesAndClassNamesMayBeMixed()
    {
        Command::replace('vendor:make-user', UserMakeCommand::class);

        $artisan = $this->getArtisan();
        $artisan->resolveCommands([VendorMakeUserCommand::class, UserMakeCommand::class]);

        $this->assertArrayNotHasKey('vendor:make-user', $artisan->all());
        $this->assertInstanceOf(UserMakeCommand::class, $artisan->find('vendor:make-user'));
    }

    public function testClassNamesMayBeReplacedByCommandNames()
    {
        Command::replace(VendorMakeUserCommand::class, 'user:make');
        Command::disable(LazyVendorMakeUserCommand::class, 'lazy:user-make');

        $artisan = $this->getArtisan();
        $artisan->resolveCommands([
            VendorMakeUserCommand::class, UserMakeCommand::class,
            LazyVendorMakeUserCommand::class, LazyUserMakeCommand::class,
        ]);
        $artisan->setContainerCommandLoader();

        $this->assertArrayNotHasKey('vendor:make-user', $artisan->all());
        $this->assertInstanceOf(UserMakeCommand::class, $artisan->find('vendor:make-user'));
        $this->assertArrayNotHasKey('lazy:vendor-make-user', $artisan->all());
        $this->assertArrayNotHasKey('lazy:user-make', $artisan->all());
    }

    public function testLazilyRegisteredCommandsMayBeReplacedUsingClassNames()
    {
        Command::replace(LazyVendorMakeUserCommand::class, LazyUserMakeCommand::class);

        $artisan = $this->getArtisan();
        $artisan->resolveCommands([LazyVendorMakeUserCommand::class, LazyUserMakeCommand::class]);
        $artisan->setContainerCommandLoader();

        $this->assertArrayNotHasKey('lazy:vendor-make-user', $artisan->all());
        $this->assertArrayHasKey('lazy:user-make', $artisan->all());
        $this->assertInstanceOf(LazyUserMakeCommand::class, $artisan->find('lazy:vendor-make-user'));
    }

    public function testCommandsMayBeDisabledUsingClassNames()
    {
        Command::disable(VendorMakeUserCommand::class, LazyVendorMakeUserCommand::class);

        $artisan = $this->getArtisan();
        $artisan->resolveCommands([VendorMakeUserCommand::class, LazyVendorMakeUserCommand::class, UserMakeCommand::class]);
        $artisan->setContainerCommandLoader();

        $this->assertFalse($artisan->has('vendor:make-user'));
        $this->assertFalse($artisan->has('lazy:vendor-make-user'));
        $this->assertArrayNotHasKey('vendor:make-user', $artisan->all());
        $this->assertArrayNotHasKey('lazy:vendor-make-user', $artisan->all());
        $this->assertArrayHasKey('user:make', $artisan->all());
    }

    public function testStateMayBeFlushed()
    {
        Command::disable('vendor:make-user');
        Command::replace('user:make', 'vendor:make-user');

        Command::flushState();

        $artisan = $this->getArtisan();
        $artisan->resolveCommands([VendorMakeUserCommand::class, UserMakeCommand::class]);

        $this->assertArrayHasKey('vendor:make-user', $artisan->all());
        $this->assertArrayHasKey('user:make', $artisan->all());
    }

    protected function getArtisan()
    {
        $container = new FoundationApplication();

        return new Application($container, new EventsDispatcher($container), $container->version());
    }
}

class VendorMakeUserCommand extends Command
{
    protected $signature = 'vendor:make-user';

    public function handle()
    {
        $this->output->write('vendor:make-user');
    }
}

class UserMakeCommand extends Command
{
    protected $signature = 'user:make';

    public function handle()
    {
        $this->output->write('user:make');
    }
}

#[AsCommand('lazy:vendor-make-user')]
class LazyVendorMakeUserCommand extends Command
{
    //
}

#[AsCommand('lazy:user-make')]
class LazyUserMakeCommand extends Command
{
    //
}
