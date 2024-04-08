<?php

declare(strict_types=1);

namespace Illuminate\Tests\Console\Fixtures;

use Illuminate\Contracts\Queue\ShouldQueue;

final class JobToTestWithSchedule implements ShouldQueue
{
    public function __invoke(): void
    {
    }
}
