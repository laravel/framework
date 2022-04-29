<?php

namespace Illuminate\Tests\Support;

class ArraySubscriber
{
    public function subscribe($events)
    {
        $events->listen(EventStub::class, [self::class, 'handle']);
    }
}
