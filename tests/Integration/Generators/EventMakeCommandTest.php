<?php

namespace Illuminate\Tests\Integration\Generators;

class EventMakeCommandTest extends TestCase
{
    protected $files = [
        'app/Events/FooCreated.php',
    ];

    public function testItCanGenerateEventFile()
    {
        $this->artisan('make:event', ['name' => 'FooCreated'])
            ->assertExitCode(0);

        $this->assertFileContains([
            'namespace App\Events;',
            'class FooCreated',
        ], 'app/Events/FooCreated.php');
    }
}
