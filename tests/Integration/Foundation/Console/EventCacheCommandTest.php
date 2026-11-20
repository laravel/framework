<?php

namespace Illuminate\Tests\Integration\Foundation\Console;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Tests\Integration\Generators\TestCase;
use JMac\Testing\Double;
use JMac\Testing\Integrations\PHPUnit\VerifiesDoubles;
use JMac\Testing\Matching\Argument;
use Orchestra\Testbench\Concerns\InteractsWithPublishedFiles;

class EventCacheCommandTest extends TestCase
{
    use InteractsWithPublishedFiles;
    use VerifiesDoubles;

    protected $files = [
        'bootstrap/cache/events.php',
    ];

    public function testEventsAreCachedAtomically()
    {
        $files = Double::for(Filesystem::class)->passthru(new Filesystem);

        $this->app->instance('files', $files);

        $this->artisan('event:cache')
            ->assertSuccessful()
            ->expectsOutputToContain('Events cached successfully');

        $this->assertFileExists($this->app->getCachedEventsPath());

        $files->received('replace')->times(1)->with($this->app->getCachedEventsPath(), Argument::type('string'));
        $files->received('put')->never();
    }
}
