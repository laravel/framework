<?php

namespace Illuminate\Tests\Console\Fixtures;

use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\EventMutex;

class FakeEventMutex implements EventMutex
{
    public function create(Event $event)
    {
    }

    public function exists(Event $event)
    {
    }

    public function forget(Event $event)
    {
    }
}
