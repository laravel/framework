<?php

declare(strict_types=1);

namespace Illuminate\Tests\Queue\Fixtures;

use Illuminate\Contracts\Queue\ShouldQueue;

final class JobToTestWithSchedule implements ShouldQueue
{
}
