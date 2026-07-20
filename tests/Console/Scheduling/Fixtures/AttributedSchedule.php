<?php

namespace Illuminate\Tests\Console\Scheduling\Fixtures;

use Illuminate\Console\Scheduling\Attributes\Scheduled;

class AttributedSchedule
{
    #[Scheduled(
        frequency: 'daily',
        at: '03:00',
        withoutOverlapping: 30,
    )]
    public function cleanup(): void
    {
        //
    }
}
