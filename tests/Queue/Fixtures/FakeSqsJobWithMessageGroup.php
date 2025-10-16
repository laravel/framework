<?php

namespace Illuminate\Tests\Queue\Fixtures;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class FakeSqsJobWithMessageGroup implements ShouldQueue
{
    use Queueable;

    public function handle(): void
    {
        //
    }

    /**
     * Message group method called by SqsQueue.
     *
     * @return string
     */
    public function messageGroup(): string
    {
        return 'group-1';
    }
}
