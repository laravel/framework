<?php

namespace Illuminate\Log\Context;

use Illuminate\Log\Context\Events\Dehydrating;
use Illuminate\Log\Context\Events\Hydrated;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Queue\Queue;
use Illuminate\Support\ServiceProvider;

class ContextServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->scoped(Repository::class);
    }

    /**
     * Boot the application services.
     *
     * @return void
     */
    public function boot()
    {
        Queue::createPayloadUsing(function ($connection, $queue, $payload) {
            $context = $this->app[Repository::class];

            $this->app['events']->dispatch(new Dehydrating($context));

            if ($context->all() === [] && $context->allHidden() === []) {
                return $payload;
            }

            return [
                ...$payload,
                'illuminate:log:context' => [
                    'data' => $context->all(),
                    'hidden' => $context->allHidden(),
                ],
            ];
        });

        $this->app['events']->listen(function (JobProcessing $event) {
            [
                'data' => $data,
                'hidden' => $hidden,
            ] = $event->job->payload()['illuminate:log:context'] ?? [
                'data' => [],
                'hidden' => [],
            ];

            $this->app['events']->dispatch(new Hydrated(
                $this->app[Repository::class]->add($data)->addHidden($hidden)
            ));
        });
    }
}
