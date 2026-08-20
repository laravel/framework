<?php

namespace Illuminate\Tests\App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;

class QueueSizeJobTwo implements ShouldQueue
{
    use Queueable;
}
