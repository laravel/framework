<?php

namespace Illuminate\Tests\Integration\Queue\Fixtures;

use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class UniqueJob implements ShouldQueue, ShouldBeUnique
{
    use Queueable;
}
