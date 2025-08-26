<?php

namespace Illuminate\Tests\Integration\Queue\Fixtures\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Auth\User;
use Illuminate\Foundation\Queue\Queueable;

class DeleteUser implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public User $user
    ) {
        log($user);
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $this->user->delete();
    }
}
