<?php

namespace Illuminate\Foundation\Testing\Concerns;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Event;

trait InteractsWithModelEvents
{
    /**
     * Fakes all internals events for the given model.
     *
     * @param  array|class-string|\Illuminate\Database\Eloquent\Model  $models
     * @param  string[]|null  $events
     * @return void
     */
    protected function fakeModelEvents($models, $events = null)
    {
        $allEvents = [
            'retrieved', 'creating', 'created',
            'updating', 'updated', 'saving',
            'saved', 'restoring', 'restored',
            'replicating', 'deleting', 'deleted',
            'forceDeleting', 'forceDeleted',
        ];

        $events ??= $allEvents;

        foreach (Arr::wrap($models) as $model) {
            $model = is_object($model) ? get_class($model) : $model;

            $events = collect($events)->map(fn ($event) => "eloquent.$event: $model")->all();

            Event::fake($events);
        }
    }

    /**
     * Assert if a model event was dispatched based on a truth-test callback.
     *
     * @param  array|class-string|\Illuminate\Database\Eloquent\Model  $models
     * @param  string[]  $events
     * @param  callable|int|null  $callback
     * @return void
     */
    protected function assertModelEventDispatched($models, $events, $callback = null)
    {
        foreach (Arr::wrap($models) as $model) {
            $model = is_object($model) ? get_class($model) : $model;

            $events = collect($events)
                ->map(fn ($event) => "eloquent.$event: $model")
                ->each(fn ($event) => Event::assertDispatched($event, $callback));
        }
    }

    /**
     * Assert if a model event was not dispatched based on a truth-test callback.
     *
     * @param  array|class-string|\Illuminate\Database\Eloquent\Model  $models
     * @param  string[]  $events
     * @param  callable|null  $callback
     * @return void
     */
    protected function assertModelEventNotDispatched($models, array $events, $callback = null)
    {
        foreach (Arr::wrap($models) as $model) {
            $model = is_object($model) ? $model::class : $model;

            $events = collect($events)->map(fn ($event) => "eloquent.$event: $model")->all();

            Event::assertNotDispatched($events, $callback);
        }
    }
}
