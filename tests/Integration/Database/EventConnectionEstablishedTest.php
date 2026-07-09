<?php

namespace Illuminate\Tests\Integration\Database;

use Illuminate\Database\Events\ConnectionEstablished;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Event;
use Orchestra\Testbench\Attributes\WithMigration;
use Orchestra\Testbench\TestCase;

use function Orchestra\Testbench\artisan;

class EventConnectionEstablishedTest extends TestCase
{
    use DatabaseMigrations;

    #[WithMigration]
    public function testItListenToEstablishedConnectionOnReconnect()
    {
        Event::fake([ConnectionEstablished::class]);

        Event::assertNotDispatched(ConnectionEstablished::class);

        artisan($this, 'migrate:fresh');

        Event::assertDispatched(ConnectionEstablished::class);
    }
}
