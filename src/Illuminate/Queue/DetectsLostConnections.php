<?php

namespace Illuminate\Queue;

use Exception;
use Illuminate\Support\Str;

trait DetectsLostConnections
{
    /**
     * Determine if the given exception was caused by a lost connection.
     *
     * @param  \Exception  $e
     * @return bool
     */
    protected function causedByLostConnection(Exception $e)
    {
        $message = $e->getMessage();

        return Str::contains($message, [
            'Broken pipe or closed connection',
            'Channel connection is closed',
        ]);
    }
}
