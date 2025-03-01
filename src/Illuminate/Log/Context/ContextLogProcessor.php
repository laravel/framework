<?php

namespace Illuminate\Log\Context;

use Illuminate\Log\Context\Repository as ContextRepository;
use Monolog\LogRecord;
use Illuminate\Contracts\Foundation\Application;
use Monolog\Processor\ProcessorInterface;

class ContextLogProcessor implements ProcessorInterface
{

    /**
     * Create a new ContextLogProcessor instance.
     *
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     * @return void
     */
    public function __construct(protected Application $app)
    {
    }

    /**
     * @param  \Monolog\LogRecord  $record
     * @return \Monolog\LogRecord
     */
    public function __invoke(LogRecord $record)
    {
        if (! $this->app->bound(ContextRepository::class)) {
            return $record;
        }

        return $record->with(extra: [
            ...$record->extra,
            ...$this->app[ContextRepository::class]->all(),
        ]);
    }
}
