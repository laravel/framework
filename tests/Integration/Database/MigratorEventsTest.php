<?php

namespace Illuminate\Tests\Integration\Database;

use Illuminate\Support\Facades\Event;
use Illuminate\Database\Events\MigrationsStarting;

class MigratorEventsTest extends DatabaseTestCase
{
    protected function migrateOptions()
    {
        return [
            '--path' => realpath(__DIR__.'/stubs/'),
            '--realpath' => true,
        ];
    }

    public function test_migratios_starting_fired_on_migrate()
    {
        Event::fake();

        $this->artisan('migrate', $this->migrateOptions());

        Event::assertDispatched(MigrationsStarting::class, function (MigrationsStarting $event){
            return in_array(realpath(__DIR__.'/stubs/'), $event->paths);
        });
    }
}
