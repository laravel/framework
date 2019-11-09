<?php

namespace Illuminate\Tests\Integration\Foundation\Fixtures\EventDiscovery\Listeners;

use Illuminate\Tests\Integration\Foundation\Fixtures\EventDiscovery\Events\EventOne;

abstract class AbstractListener
{
    abstract public function handle(EventOne $event);
}
