<?php

namespace Illuminate\Tests\Integration\Foundation\Console;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Tests\Integration\Generators\TestCase;
use Mockery as m;
use Orchestra\Testbench\Concerns\InteractsWithPublishedFiles;

class EventCacheCommandTest extends TestCase
{
    use InteractsWithPublishedFiles;

    protected $files = [
        'bootstrap/cache/events.php',
    ];

    public function testEventsAreCachedAtomically()
    {
        $files = m::mock(Filesystem::class)->makePartial();
        $files->shouldReceive('replace')->once()->with($this->app->getCachedEventsPath(), m::type('string'))->passthru();

        $this->app->instance('files', $files);

        $this->artisan('event:cache')
            ->assertSuccessful()
            ->expectsOutputToContain('Events cached successfully');

        $this->assertFileExists($this->app->getCachedEventsPath());
    }
}
