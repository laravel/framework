<?php

namespace Illuminate\Queue\Jobs;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Queue\CallQueuedHandler as QueueCallQueuedHandler;
use Illuminate\Events\CallQueuedHandler as EventsCallQueuedHandler;

class JobName
{
    /**
     * Parse the given job name into a class / method array.
     *
     * @param  string  $job
     * @return array
     */
    public static function parse($job)
    {
        return Str::parseCallback($job, 'fire');
    }

    /**
     * Get the resolved name of the queued job class.
     *
     * @param  string  $name
     * @param  array  $payload
     * @return string
     */
    public static function resolve($name, $payload)
    {
        if ($name === QueueCallQueuedHandler::class.'@call') {
            return Arr::get($payload, 'data.commandName', $name);
        }

        if ($name === EventsCallQueuedHandler::class.'@call') {
            return $payload['data']['class'].'@'.$payload['data']['method'];
        }

        return $name;
    }
}
