<?php

namespace Illuminate\Log;

if (! function_exists('Illuminate\Log\log')) {
    /**
     * Log a debug message to the logs.
     *
     * @param  string|null  $message
     * @param  array  $context
     * @return ($message is null ? \Illuminate\Log\LogManager : null)
     */
    function log($message = null, array $context = []): ?LogManager
    {
        return logger($message, $context);
    }
}
