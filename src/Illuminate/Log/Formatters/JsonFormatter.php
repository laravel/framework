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

        try {
            $handler = Container::getInstance()->make(ExceptionHandler::class);
        } catch (Throwable) {
            return $response;
        }

        if (method_exists($handler, 'createExceptionContext')) {
            $response = array_merge(
                $this->normalize($handler->createExceptionContext($e), $depth + 1),
                $response
            );
        } elseif (method_exists($e, 'context')) {
            $response = array_merge(
                $this->normalize($e->context(), $depth + 1),
                $response
            );

            ExceptionContextState::reportContextBuilt($e);
        }

        return $response;
    }
}
