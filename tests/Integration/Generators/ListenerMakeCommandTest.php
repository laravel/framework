<?php

namespace Illuminate\Tests\Integration\Generators;

class ListenerMakeCommandTest extends TestCase
{
    protected $files = [
        'app/Listeners/Foo.php',
        'tests/Feature/Listeners/FooTest.php',
    ];

    public function testItCanGenerateListenerFile()
    {
        $this->artisan('make:listener', ['name' => 'Foo'])
            ->assertExitCode(0);

        $this->assertFileContains([
            'namespace App\Listeners;',
            'class Foo',
            'public function handle(object $event)',
        ], 'app/Listeners/Foo.php');

        $this->assertFileNotContains([
            'class Foo implements ShouldQueue',
        ], 'app/Listeners/Foo.php');
    }

    public function testItCanGenerateListenerFileForEvent()
    {
        $this->artisan('make:listener', ['name' => 'Foo', '--event' => 'FooCreated'])
            ->assertExitCode(0);

        $this->assertFileContains([
            'namespace App\Listeners;',
            'use App\Events\FooCreated;',
            'class Foo',
            'public function handle(FooCreated $event)',
        ], 'app/Listeners/Foo.php');
    }

    public function testItCanGenerateListenerFileForIlluminateEvent()
    {
        $this->artisan('make:listener', ['name' => 'Foo', '--event' => 'Illuminate\Auth\Events\Login'])
            ->assertExitCode(0);

        $this->assertFileContains([
            'namespace App\Listeners;',
            'use Illuminate\Auth\Events\Login;',
            'class Foo',
            'public function handle(Login $event)',
        ], 'app/Listeners/Foo.php');
    }

    public function testItCanGenerateQueuedListenerFile()
    {
        $this->artisan('make:listener', ['name' => 'Foo', '--queued' => true])
            ->assertExitCode(0);

        $this->assertFileContains([
            'namespace App\Listeners;',
            'use Illuminate\Contracts\Queue\ShouldQueue;',
            'use Illuminate\Queue\InteractsWithQueue;',
            'class Foo implements ShouldQueue',
            'public function handle(object $event)',
        ], 'app/Listeners/Foo.php');
    }

    public function testItCanGenerateQueuedListenerFileForEvent()
    {
        $this->artisan('make:listener', ['name' => 'Foo', '--queued' => true, '--event' => 'FooCreated'])
            ->assertExitCode(0);

        $this->assertFileContains([
            'namespace App\Listeners;',
            'use App\Events\FooCreated;',
            'use Illuminate\Contracts\Queue\ShouldQueue;',
            'use Illuminate\Queue\InteractsWithQueue;',
            'class Foo implements ShouldQueue',
            'public function handle(FooCreated $event)',
        ], 'app/Listeners/Foo.php');
    }

    public function testItCanGenerateQueuedListenerFileForIlluminateEvent()
    {
        $this->artisan('make:listener', ['name' => 'Foo', '--queued' => true, '--event' => 'Illuminate\Auth\Events\Login'])
            ->assertExitCode(0);

        $this->assertFileContains([
            'namespace App\Listeners;',
            'use Illuminate\Auth\Events\Login;',
            'use Illuminate\Contracts\Queue\ShouldQueue;',
            'use Illuminate\Queue\InteractsWithQueue;',
            'class Foo implements ShouldQueue',
            'public function handle(Login $event)',
        ], 'app/Listeners/Foo.php');
    }

    public function testItCanGenerateQueuedListenerFileWithTest()
    {
        $this->artisan('make:listener', ['name' => 'Foo', '--test' => true])
            ->assertExitCode(0);

        $this->assertFilenameExists('app/Listeners/Foo.php');
        $this->assertFilenameExists('tests/Feature/Listeners/FooTest.php');
    }
}
