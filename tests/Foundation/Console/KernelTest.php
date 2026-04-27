<?php

namespace Illuminate\Tests\Foundation\Console;

use Illuminate\Events\Dispatcher;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Console\Kernel;
use Illuminate\Foundation\Events\Terminating;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\StringInput;

use function Illuminate\Support\artisan_binary;

class KernelTest extends TestCase
{
    #[RunInSeparateProcess]
    public function testItUsesTheInvokedArtisanScriptAsTheArtisanBinary()
    {
        $_SERVER['argv'][0] = '/home/laravel/artisan';

        $app = new Application;
        $events = new Dispatcher($app);
        $app->instance('events', $events);

        new Kernel($app, $events);

        $this->assertSame('/home/laravel/artisan', artisan_binary());
    }

    #[RunInSeparateProcess]
    public function testItFallsBackToArtisanWhenArgvIsUnavailable()
    {
        unset($_SERVER['argv']);

        $app = new Application;
        $events = new Dispatcher($app);
        $app->instance('events', $events);

        new Kernel($app, $events);

        $this->assertSame('artisan', artisan_binary());
    }

    public function testItDispatchesTerminatingEvent()
    {
        $called = [];
        $app = new Application;
        $events = new Dispatcher($app);
        $app->instance('events', $events);
        $kernel = new Kernel($app, $events);
        $events->listen(function (Terminating $terminating) use (&$called) {
            $called[] = 'terminating event';
        });
        $app->terminating(function () use (&$called) {
            $called[] = 'terminating callback';
        });

        $kernel->terminate(new StringInput('tinker'), 0);

        $this->assertSame([
            'terminating event',
            'terminating callback',
        ], $called);
    }
}
