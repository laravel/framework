<?php

namespace Illuminate\Console\Events;

class ArtisanStarting
{
    /**
     * Create a new event instance.
     *
     * @param  \Illuminate\Console\Application  $artisan  The Artisan application instance.
     * @return void
     */
    public function __construct(
        public $artisan,
    ) {
    }
}
