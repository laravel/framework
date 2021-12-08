<?php

namespace Illuminate\Database\Console\Seeds;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Events\NullDispatcher;
use Illuminate\Support\Hooks\Hook;

trait WithoutModelEvents
{
    /**
     * Prevent model events from being dispatched when the seeder is invoked.
     *
     * @return Hook
     */
    public static function withoutModelEvents(): Hook
    {
        return Hook::make('run', function () {
            if (! $dispatcher = Model::getEventDispatcher()) {
                return null;
            }

            Model::setEventDispatcher(new NullDispatcher($dispatcher));

            return fn () => Model::setEventDispatcher($dispatcher);
        });
    }
}
