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

        try {
            $handler = Container::getInstance()->make(ExceptionHandler::class);
        } catch (Throwable) {
            return $response;
        }

        if ((! method_exists($handler, 'isReporting')) || ! $handler->isReporting($e)) {
            if (method_exists($handler, 'buildContextForException')
                && is_array($normalizedHandlerExceptionContext = $this->normalize($handler->buildContextForException($e), $depth + 1))
            ) {
                $response = array_merge(
                    $normalizedHandlerExceptionContext,
                    $response
                );
            } elseif (method_exists($e, 'context')) {
                $exceptionContext = $this->normalize($e->context(), $depth + 1);

                if (is_array($exceptionContext)) {
                    $response = array_merge(
                        $exceptionContext,
                        $response
                    );
                }
            }
        }

        return $response;
    }
}
