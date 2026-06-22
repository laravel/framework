<?php

namespace Illuminate\Tests\Foundation;

use Illuminate\Foundation\Application;
use Illuminate\Foundation\DevCommand;
use Illuminate\Foundation\DevCommandColor;
use Illuminate\Foundation\DevCommands;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class FoundationDevCommandsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $ref = new ReflectionClass(DevCommands::class);

        foreach (['commands', 'except', 'only'] as $prop) {
            $ref->getProperty($prop)->setValue(null, []);
        }

        $ref->getProperty('colorCount')->setValue(null, 0);

        $app = new Application(__DIR__);
        $app['env'] = 'testing';
    }

    public function testRegisterAddsCommand()
    {
        $devCommand = DevCommands::register('echo hello', 'greeter');

        $this->assertInstanceOf(DevCommand::class, $devCommand);

        $commands = DevCommands::commands();

        $this->assertCount(1, $commands);
        $this->assertSame('echo hello', $commands[0]['command']);
        $this->assertSame('greeter', $commands[0]['name']);
    }

    public function testRegisterDerivesNameFromCommand()
    {
        DevCommands::register('echo hello world');

        $commands = DevCommands::commands();

        $this->assertSame('echo', $commands[0]['name']);
    }

    public function testArtisanPrefixesCommand()
    {
        DevCommands::artisan('serve --host=localhost', 'server');

        $commands = DevCommands::commands();

        $this->assertSame('php artisan serve --host=localhost', $commands[0]['command']);
        $this->assertSame('server', $commands[0]['name']);
    }

    public function testArtisanDerivesNameFromCommand()
    {
        DevCommands::artisan('queue:listen --tries=1');

        $commands = DevCommands::commands();

        $this->assertSame('queue:listen', $commands[0]['name']);
    }

    public function testExceptExcludesCommands()
    {
        DevCommands::register('echo one', 'one');
        DevCommands::register('echo two', 'two');
        DevCommands::register('echo three', 'three');

        DevCommands::except('two');

        $commands = DevCommands::commands();

        $this->assertCount(2, $commands);
        $this->assertSame('one', $commands[0]['name']);
        $this->assertSame('three', $commands[1]['name']);
    }

    public function testOnlyIncludesOnlySpecifiedCommands()
    {
        DevCommands::register('echo one', 'one');
        DevCommands::register('echo two', 'two');
        DevCommands::register('echo three', 'three');

        DevCommands::only('one', 'three');

        $commands = DevCommands::commands();

        $this->assertCount(2, $commands);
        $this->assertSame('one', $commands[0]['name']);
        $this->assertSame('three', $commands[1]['name']);
    }

    public function testOnlyTakesPrecedenceOverExcept()
    {
        DevCommands::register('echo one', 'one');
        DevCommands::register('echo two', 'two');
        DevCommands::register('echo three', 'three');

        DevCommands::only('one', 'two');
        DevCommands::except('two');

        $commands = DevCommands::commands();

        $this->assertCount(1, $commands);
        $this->assertSame('one', $commands[0]['name']);
    }

    public function testCommandsGetAutoAssignedColors()
    {
        DevCommands::register('echo one', 'one');
        DevCommands::register('echo two', 'two');

        $commands = DevCommands::commands();

        $this->assertNotNull($commands[0]['color']);
        $this->assertNotNull($commands[1]['color']);
        $this->assertNotSame($commands[0]['color'], $commands[1]['color']);
    }

    public function testExplicitColorIsPreserved()
    {
        DevCommands::register('echo one', 'one')->pink();
        DevCommands::register('echo two', 'two');

        $commands = DevCommands::commands();

        $this->assertSame(DevCommandColor::PINK->value, $commands[0]['color']);
        $this->assertNotSame(DevCommandColor::PINK->value, $commands[1]['color']);
    }

    public function testAutoColorSkipsExplicitlyUsedColors()
    {
        DevCommands::register('echo one', 'one')->blue();
        DevCommands::register('echo two', 'two');

        $commands = DevCommands::commands();

        $this->assertSame(DevCommandColor::BLUE->value, $commands[0]['color']);
        $this->assertNotSame(DevCommandColor::BLUE->value, $commands[1]['color']);
    }

    public function testColorsRecycleWhenAllUsed()
    {
        DevCommands::register('cmd1', 'c1');
        DevCommands::register('cmd2', 'c2');
        DevCommands::register('cmd3', 'c3');
        DevCommands::register('cmd4', 'c4');
        DevCommands::register('cmd5', 'c5');
        DevCommands::register('cmd6', 'c6');
        DevCommands::register('cmd7', 'c7');

        $commands = DevCommands::commands();

        $this->assertCount(7, $commands);

        foreach ($commands as $command) {
            $this->assertNotNull($command['color']);
        }
    }

    public function testRegisteringCommandWithSameNameOverwritesPrevious()
    {
        DevCommands::register('echo old', 'myname');
        DevCommands::register('echo new', 'myname');

        $commands = DevCommands::commands();

        $this->assertCount(1, $commands);
        $this->assertSame('echo new', $commands[0]['command']);
    }

    public function testRegisterDefaultsRegistersExpectedCommands()
    {
        DevCommands::registerDefaults();

        $commands = DevCommands::commands();

        $this->assertCount(4, $commands);

        $names = array_column($commands, 'name');
        $this->assertContains('server', $names);
        $this->assertContains('queue', $names);
        $this->assertContains('logs', $names);
        $this->assertContains('vite', $names);
    }

    public function testRegisteredCommandIncludesSource()
    {
        DevCommands::register('echo hello', 'greeter');

        $commands = DevCommands::commands();

        $this->assertArrayHasKey('source', $commands[0]);
        $this->assertStringContainsString(__CLASS__, $commands[0]['source']);
    }
}
