<?php

use Illuminate\Queue\Events\JobQueueing;

use function PHPStan\Testing\assertType;

$instance = new JobQueueing(
    connectionName: 'connection',
    queue: null,
    job: fn () => null,
    payload: '{}',
    delay: null,
);

/**
 * @see testQueueMayBeNullForJobQueueingAndJobQueuedEvent
 */
assertType('string|null', $instance->queue);
