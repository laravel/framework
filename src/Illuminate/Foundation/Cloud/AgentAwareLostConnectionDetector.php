<?php

namespace Illuminate\Foundation\Cloud;

use Illuminate\Contracts\Database\LostConnectionDetector;
use Throwable;

/**
 * Decorates the framework's lost-connection detector so the managed queue
 * worker treats an unreachable cloud-agent runtime socket as a lost connection
 * and exits.
 *
 * This deliberately diverges from the SQS driver. An unreachable SQS endpoint
 * throws an SqsException the detector does not recognize, so the worker just
 * reports it and retries — a remote broker blip is transient. The cloud-agent
 * is different: it runs in the pod and is never restarted on its own, so if it
 * dies (e.g. an OOM) nothing recovers it and retrying forever is pointless. The
 * only fix is a full pod restart, which surfacing its loss as a lost connection
 * triggers (the worker exits). Every other exception is delegated untouched.
 */
class AgentAwareLostConnectionDetector implements LostConnectionDetector
{
    /**
     * Create a new detector instance.
     */
    public function __construct(
        protected LostConnectionDetector $detector,
    ) {
        //
    }

    /**
     * Determine if the given exception was caused by a lost connection.
     */
    public function causedByLostConnection(Throwable $e): bool
    {
        return $e instanceof AgentUnreachableException
            || $this->detector->causedByLostConnection($e);
    }
}
