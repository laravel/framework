<?php

namespace Illuminate\Tests\Queue\Fixtures;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class FakeSqsJobWithDeduplication implements ShouldQueue
{
    use Queueable;

    protected $testDeduplicationId = 'test-deduplication-id';

    public function handle(): void
    {
        //
    }

    /**
     * Deduplication ID method called by SqsQueue.
     *
     * @return string
     */
    public function deduplicationId(): string
    {
        return (string) $this->testDeduplicationId;
    }

    /**
     * Helper method to allow a test to specify the deduplication ID to use.
     *
     * @param  string  $id
     * @return $this
     */
    public function useDeduplicationId($id): static
    {
        $this->testDeduplicationId = $id;

        return $this;
    }
}
