<?php

namespace Illuminate\Tests\App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;

class AfterCommitJob implements ShouldQueue
{
    public $afterCommit = true;
}
