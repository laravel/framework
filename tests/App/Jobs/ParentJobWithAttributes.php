<?php

namespace Illuminate\Tests\App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\Attributes\Backoff;
use Illuminate\Queue\Attributes\FailOnTimeout;
use Illuminate\Queue\Attributes\MaxExceptions;
use Illuminate\Queue\Attributes\Timeout;
use Illuminate\Queue\Attributes\Tries;

#[Backoff(9)]
#[FailOnTimeout]
#[MaxExceptions(3)]
#[Timeout(40)]
#[Tries(2)]
abstract class ParentJobWithAttributes implements ShouldQueue
{
}
