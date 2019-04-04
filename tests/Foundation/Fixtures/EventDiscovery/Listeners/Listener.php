<?php

namespace Illuminate\Tests\Foundation\Fixtures\EventDiscovery\Listeners;

use Illuminate\Tests\Foundation\Fixtures\EventDiscovery\Events\EventOne;
use Illuminate\Tests\Foundation\Fixtures\EventDiscovery\Events\EventTwo;

class Listener
{
    public function handle(EventOne $event)
    {
        //
    }

    public function handleEventOne(EventOne $event)
    {
        //
    }

    public function handleEventTwo(EventTwo $event)
    {
        //
    }
}
