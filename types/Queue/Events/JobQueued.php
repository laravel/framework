<?php

use Illuminate\Queue\Events\JobQueued;

use function PHPStan\Testing\assertType;

$instance = new JobQueued(
    connectionName: 'connection',
    queue: null,
    id: 'id',
    job: fn () => null,
    payload: '{}',
    delay: null,
);

/**
 * @see testQueueMayBeNullForJobQueueingAndJobQueuedEvent
 */
assertType('string|null', $instance->queue);
