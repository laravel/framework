<?php

namespace Illuminate\Tests\Integration\Generators;

class ObserverMakeCommandTest extends TestCase
{
    protected $files = [
        'app/Observers/FooObserver.php',
    ];

    public function testItCanGenerateObserverFile()
    {
        $this->artisan('make:observer', ['name' => 'FooObserver'])
            ->assertExitCode(0);

        $this->assertFileContains([
            'namespace App\Observers;',
            'class FooObserver',
        ], 'app/Observers/FooObserver.php');
    }

    public function testItCanGenerateObserverFileWithModel()
    {
        $this->artisan('make:observer', ['name' => 'FooObserver', '--model' => 'Foo'])
            ->assertExitCode(0);

        $this->assertFileContains([
            'namespace App\Observers;',
            'use App\Models\Foo;',
            'class FooObserver',
            'public function created(Foo $foo)',
            'public function updated(Foo $foo)',
            'public function deleted(Foo $foo)',
        ], 'app/Observers/FooObserver.php');
    }

    public function testItCanGenerateObserverFileWithModelWithSoftDelete()
    {
        $this->artisan('make:observer', ['name' => 'FooObserver', '--model' => 'Foo', '--softDelete' => true])
            ->assertExitCode(0);

        $this->assertFileContains([
            'namespace App\Observers;',
            'use App\Models\Foo;',
            'class FooObserver',
            'public function created(Foo $foo)',
            'public function updated(Foo $foo)',
            'public function deleted(Foo $foo)',
            'public function softDeleted(Foo $foo)',
            'public function restored(Foo $foo)',
            'public function forceDeleted(Foo $foo)',
        ], 'app/Observers/FooObserver.php');
    }
}
