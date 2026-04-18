<?php

namespace Illuminate\Log\Formatters;

use Illuminate\Container\Container;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Monolog\Formatter\JsonFormatter as MonologJsonFormatter;
use Throwable;

class JsonFormatter extends MonologJsonFormatter
{
    #[\Override]
    protected function normalizeException(Throwable $e, int $depth = 0): array
    {
        $response = parent::normalizeException($e, $depth);

        if (ExceptionContextState::hasBuiltContextFor($e)) {
            return $response;
        }

        $handler = Container::getInstance()->make(ExceptionHandler::class);

        if (method_exists($handler, 'createExceptionContext')) {
            $response = array_merge(
                $handler->createExceptionContext($e),
                $response
            );
        } elseif (method_exists($e, 'context')) {
            $response = array_merge(
                $e->context(),
                $response
            );

            ExceptionContextState::reportContextBuilt($e);
        }

        return $response;
    }
}
