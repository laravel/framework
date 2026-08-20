<?php

namespace Illuminate\Tests\App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Tests\Integration\Queue\JobDispatchingTestQueueTrait;

class JobWithTraitQueueAttribute implements ShouldQueue
{
    use Dispatchable, JobDispatchingTestQueueTrait;
}
