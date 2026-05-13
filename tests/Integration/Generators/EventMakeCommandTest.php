<?php

namespace Illuminate\Tests\Integration\Generators;

class EventMakeCommandTest extends TestCase
{
    protected $files = [
        'app/Events/FooCreated.php',
    ];

    public function testItCanGenerateBareMinimumEventFile()
    {
        $this->artisan('make:event', ['name' => 'FooCreated'])
            ->assertExitCode(0);

        $this->assertFileContains([
            'namespace App\Events;',
            'class FooCreated',
            'use Dispatchable, SerializesModels;',
        ], 'app/Events/FooCreated.php');
    }

    public function testItCanGenerateBroadcastEventFile()
    {
        $this->artisan('make:event', ['name' => 'FooCreated', '--broadcast' => true])
            ->assertExitCode(0);

        $this->assertFileContains([
            'namespace App\Events;',
            'use Illuminate\Broadcasting\Channel;',
            'use Illuminate\Broadcasting\InteractsWithSockets;',
            'use Illuminate\Broadcasting\PresenceChannel;',
            'use Illuminate\Broadcasting\PrivateChannel;',
            'use Illuminate\Contracts\Broadcasting\ShouldBroadcast;',
            'class FooCreated implements ShouldBroadcast',
            'use Dispatchable, InteractsWithSockets, SerializesModels;',
        ], 'app/Events/FooCreated.php');
    }
}
