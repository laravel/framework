<?php

namespace Illuminate\Tests\App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\Attributes\Queue as QueueAttribute;
use Illuminate\Tests\Integration\Queue\JobDispatchingTestQueueTrait;

#[QueueAttribute('high')]
class JobWithClassQueueAttributeOverridingTrait implements ShouldQueue
{
    use Dispatchable, JobDispatchingTestQueueTrait;
}
