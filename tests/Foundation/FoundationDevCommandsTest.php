<?php

namespace Illuminate\Tests\Foundation;

use Illuminate\Container\Container;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\DevCommand;
use Illuminate\Foundation\DevCommandColor;
use Illuminate\Foundation\DevCommandMode;
use Illuminate\Foundation\DevCommands;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Facades\File;
use Mockery;
use PHPUnit\Framework\Attributes\RequiresOperatingSystem;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class FoundationDevCommandsTest extends TestCase
{
    protected function setUp(): void
    {
        $ref = new ReflectionClass(DevCommands::class);

        foreach ([
            'commands' => [],
            'except' => [],
            'only' => [],
            'colorCount' => 0,
            'mode' => DevCommandMode::TABS,
            'withTimestamps' => false,
            'autoRestart' => true,
            'bufferSize' => null,
            'streamBufferSize' => null,
            'withoutVendorCommands' => false,
            'withoutDefaultCommands' => false,
        ] as $prop => $value) {
            $ref->getProperty($prop)->setValue(null, $value);
        }

        $app = new Application(__DIR__);
        $app['env'] = 'testing';

        File::swap(new Filesystem);
    }

    protected function tearDown(): void
    {
        Facade::clearResolvedInstances();
        Container::setInstance(null);

        parent::tearDown();
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

    public function testRegisteringCommandWithSameNameAndSamePriorityOverwritesPrevious()
    {
        DevCommands::register('echo old', 'myname');
        DevCommands::register('echo new', 'myname');

        $commands = DevCommands::commands();

        $this->assertCount(1, $commands);
        $this->assertSame('echo new', $commands[0]['command']);
    }

    public function testUserlandOverwritesVendorPriority()
    {
        $ref = new ReflectionClass(DevCommands::class);
        $ref->getProperty('commands')->setValue(null, [
            'myname' => new DevCommand('echo vendor', [], 'myname', DevCommand::PRIORITY_VENDOR),
        ]);

        // register() resolves as userland from test code — should overwrite vendor
        DevCommands::register('echo userland', 'myname');

        $result = DevCommands::commands();
        $this->assertSame('echo userland', collect($result)->firstWhere('name', 'myname')['command']);
    }

    public function testUserlandOverwritesDefaultPriority()
    {
        // registerDefaults() gets DEFAULT priority, then register() gets USERLAND
        DevCommands::registerDefaults();
        DevCommands::register('custom-server', 'server');

        $result = DevCommands::commands();
        $server = collect($result)->firstWhere('name', 'server');
        $this->assertSame('custom-server', $server['command']);
        $this->assertSame(DevCommand::PRIORITY_USERLAND, $server['priority']);
    }

    public function testDefaultDoesNotOverwriteUserlandPriority()
    {
        $ref = new ReflectionClass(DevCommands::class);
        $ref->getProperty('commands')->setValue(null, [
            'server' => new DevCommand('userland-server', [], 'server', DevCommand::PRIORITY_USERLAND),
        ]);

        // registerDefaults() gets DEFAULT priority — should NOT overwrite userland
        DevCommands::registerDefaults();

        $result = DevCommands::commands();
        $this->assertSame('userland-server', collect($result)->firstWhere('name', 'server')['command']);
    }

    public function testDefaultDoesNotOverwriteVendorPriority()
    {
        $ref = new ReflectionClass(DevCommands::class);
        $ref->getProperty('commands')->setValue(null, [
            'server' => new DevCommand('vendor-server', [], 'server', DevCommand::PRIORITY_VENDOR),
        ]);

        // registerDefaults() gets DEFAULT priority — should NOT overwrite vendor
        DevCommands::registerDefaults();

        $result = DevCommands::commands();
        $this->assertSame('vendor-server', collect($result)->firstWhere('name', 'server')['command']);
    }

    public function testDefaultPriorityIsLowest()
    {
        DevCommands::registerDefaults();

        $commands = DevCommands::commands();
        $serverCommand = collect($commands)->firstWhere('name', 'server');

        $this->assertSame(DevCommand::PRIORITY_DEFAULT, $serverCommand['priority']);
    }

    public function testUserlandRegistrationGetsUserlandPriority()
    {
        DevCommands::register('echo hello', 'greeter');

        $commands = DevCommands::commands();

        $this->assertSame(DevCommand::PRIORITY_USERLAND, $commands[0]['priority']);
    }

    public function testResolvePriorityDetectsUserlandThroughDevCommandsFrame()
    {
        $ref = new ReflectionClass(DevCommands::class);
        $method = $ref->getMethod('resolvePriority');

        $trace = [
            ['file' => $ref->getFileName(), 'line' => 99, 'function' => 'register', 'class' => DevCommands::class],
            ['file' => base_path('app/Providers/AppServiceProvider.php'), 'line' => 19, 'function' => 'artisan', 'class' => DevCommands::class],
            ['file' => base_path('vendor/laravel/framework/src/Illuminate/Foundation/Application.php'), 'line' => 896, 'function' => 'register', 'class' => 'App\\Providers\\AppServiceProvider'],
        ];

        $this->assertSame(DevCommand::PRIORITY_USERLAND, $method->invoke(null, $trace));
    }

    public function testResolvePriorityDetectsVendor()
    {
        $ref = new ReflectionClass(DevCommands::class);
        $method = $ref->getMethod('resolvePriority');

        $trace = [
            ['file' => $ref->getFileName(), 'line' => 99, 'function' => 'register', 'class' => DevCommands::class],
            ['file' => base_path('vendor/some-package/src/ServiceProvider.php'), 'line' => 10, 'function' => 'register', 'class' => DevCommands::class],
            ['file' => base_path('vendor/laravel/framework/src/Illuminate/Foundation/Application.php'), 'line' => 896, 'function' => 'register', 'class' => 'Some\\Package\\ServiceProvider'],
        ];

        $this->assertSame(DevCommand::PRIORITY_VENDOR, $method->invoke(null, $trace));
    }

    public function testResolvePriorityDetectsUserlandCallingVendorHelper()
    {
        $ref = new ReflectionClass(DevCommands::class);
        $method = $ref->getMethod('resolvePriority');

        $trace = [
            ['file' => base_path('vendor/some-package/src/Helper.php'), 'line' => 10, 'function' => 'register', 'class' => DevCommands::class],
            ['file' => base_path('app/Providers/AppServiceProvider.php'), 'line' => 25, 'function' => 'setupDev', 'class' => 'Some\\Package\\Helper'],
            ['file' => base_path('vendor/laravel/framework/src/Illuminate/Foundation/Application.php'), 'line' => 896, 'function' => 'register', 'class' => 'App\\Providers\\AppServiceProvider'],
        ];

        $this->assertSame(DevCommand::PRIORITY_USERLAND, $method->invoke(null, $trace));
    }

    #[RequiresOperatingSystem('Linux|Darwin')]
    public function testRegisterDefaultsRegistersExpectedCommands()
    {
        File::shouldReceive('exists')->with(base_path('package.json'))->andReturnTrue();

        $provider = Mockery::mock('alias:Laravel\Pail\PailServiceProvider');
        $provider->shouldReceive('register');

        Application::getInstance()->register($provider);

        DevCommands::registerDefaults();

        $commands = DevCommands::commands();

        $this->assertCount(4, $commands);

        $names = array_column($commands, 'name');
        $this->assertContains('server', $names);
        $this->assertSame('php artisan serve', collect($commands)->firstWhere('name', 'server')['command']);
        $this->assertContains('queue', $names);
        $this->assertContains('logs', $names);
        $this->assertContains('vite', $names);
    }

    #[RequiresOperatingSystem('Linux|Darwin')]
    public function testRegisterDefaultsExcludesPailWhenNotInstalled()
    {
        File::shouldReceive('exists')->with(base_path('package.json'))->andReturnTrue();

        DevCommands::registerDefaults();

        $commands = DevCommands::commands();

        $this->assertCount(3, $commands);

        $names = array_column($commands, 'name');
        $this->assertContains('server', $names);
        $this->assertContains('queue', $names);
        $this->assertContains('vite', $names);
        $this->assertNotContains('logs', $names);
    }

    #[RequiresOperatingSystem('Windows')]
    public function testRegisterDefaultsExcludesPailOnWindows()
    {
        File::shouldReceive('exists')->with(base_path('package.json'))->andReturnTrue();

        DevCommands::registerDefaults();

        $commands = DevCommands::commands();

        $this->assertCount(3, $commands);

        $names = array_column($commands, 'name');
        $this->assertContains('server', $names);
        $this->assertContains('queue', $names);
        $this->assertContains('vite', $names);
        $this->assertNotContains('logs', $names);
    }

    public function testRegisterDefaultsExcludesViteWithoutPackageJson()
    {
        File::shouldReceive('exists')->with(base_path('package.json'))->andReturnFalse();

        DevCommands::registerDefaults();

        $names = array_column(DevCommands::commands(), 'name');

        $this->assertContains('server', $names);
        $this->assertContains('queue', $names);
        $this->assertNotContains('vite', $names);
    }

    public function testRegisteredCommandIncludesSource()
    {
        DevCommands::register('echo hello', 'greeter');

        $commands = DevCommands::commands();

        $this->assertArrayHasKey('source', $commands[0]);
        $this->assertIsArray($commands[0]['source']);
        $this->assertSame(__CLASS__, $commands[0]['source']['class']);
    }

    public function testVendorCommandsAreIncludedByDefault()
    {
        $ref = new ReflectionClass(DevCommands::class);
        $ref->getProperty('commands')->setValue(null, [
            'vendor' => new DevCommand('echo vendor', [], 'vendor', DevCommand::PRIORITY_VENDOR),
        ]);

        DevCommands::register('echo local', 'local');

        $names = array_column(DevCommands::commands(), 'name');

        $this->assertContains('vendor', $names);
        $this->assertContains('local', $names);
    }

    public function testWithoutVendorCommandsExcludesOnlyVendorCommands()
    {
        $ref = new ReflectionClass(DevCommands::class);
        $ref->getProperty('commands')->setValue(null, [
            'server' => new DevCommand('php artisan serve', [], 'server', DevCommand::PRIORITY_DEFAULT),
            'vendor' => new DevCommand('echo vendor', [], 'vendor', DevCommand::PRIORITY_VENDOR),
        ]);

        DevCommands::register('echo local', 'local');

        DevCommands::withoutVendorCommands();

        $names = array_column(DevCommands::commands(), 'name');

        $this->assertNotContains('vendor', $names);
        $this->assertContains('server', $names);
        $this->assertContains('local', $names);
    }

    public function testWithoutDefaultCommandsExcludesOnlyFrameworkDefaultCommands()
    {
        $ref = new ReflectionClass(DevCommands::class);
        $ref->getProperty('commands')->setValue(null, [
            'server' => new DevCommand('php artisan serve', [], 'server', DevCommand::PRIORITY_DEFAULT),
            'vendor' => new DevCommand('echo vendor', [], 'vendor', DevCommand::PRIORITY_VENDOR),
        ]);

        DevCommands::register('echo local', 'local');

        DevCommands::withoutDefaultCommands();

        $names = array_column(DevCommands::commands(), 'name');

        $this->assertNotContains('server', $names);
        $this->assertContains('vendor', $names);
        $this->assertContains('local', $names);
    }

    public function testWithoutVendorAndDefaultCommandsKeepsOnlyLocalCommands()
    {
        $ref = new ReflectionClass(DevCommands::class);
        $ref->getProperty('commands')->setValue(null, [
            'server' => new DevCommand('php artisan serve', [], 'server', DevCommand::PRIORITY_DEFAULT),
            'vendor' => new DevCommand('echo vendor', [], 'vendor', DevCommand::PRIORITY_VENDOR),
        ]);

        DevCommands::register('echo local', 'local');

        DevCommands::withoutVendorCommands();
        DevCommands::withoutDefaultCommands();

        $names = array_column(DevCommands::commands(), 'name');

        $this->assertSame(['local'], $names);
    }
}
