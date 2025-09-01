<?php

namespace Illuminate\Foundation\Testing;

use Illuminate\Contracts\Console\Kernel as ConsoleKernel;
use Illuminate\Testing\Attributes\SetUp;

trait WithConsoleEvents
{
    /**
     * Register console events.
     *
     * @return void
     */
    #[SetUp]
    protected function setUpWithConsoleEvents()
    {
        $this->app[ConsoleKernel::class]->rerouteSymfonyCommandEvents();
    }
}
