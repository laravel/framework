<?php

namespace Illuminate\Database\Console\Seeds;

use Illuminate\Database\Eloquent\Model;

trait WithoutModelTimestamps
{
    /**
     * Prevent model timestamps from being updated by the given callback.
     */
    public function withoutModelTimestamps(callable $callback): callable
    {
        return fn () => Model::withoutTimestamps($callback);
    }
}
