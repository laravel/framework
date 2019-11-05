<?php

namespace Illuminate\Routing;

use Exception;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\Request;
use Illuminate\Pipeline\Pipeline as BasePipeline;

/**
 * This extended pipeline catches any exceptions that occur during each slice.
 *
 * The exceptions are converted to HTTP responses for proper middleware handling.
 */
class Pipeline extends BasePipeline
{
    /**
     * Handles the value returned from each pipe before passing it to the next.
     *
     * @param  mixed $carry
     * @return mixed
     */
    protected function handleCarry($carry)
    {
        return $carry instanceof Responsable
            ? $carry->toResponse($this->getContainer()->make(Request::class))
            : $carry;
    }

    /**
     * Handle the given exception.
     *
     * @param  mixed  $passable
     * @param  \Exception  $e
     * @return mixed
     *
     * @throws \Exception
     */
    protected function handleException($passable, Exception $e)
    {
        if (! $this->container->bound(ExceptionHandler::class) ||
            ! $passable instanceof Request) {
            throw $e;
        }

        $handler = $this->container->make(ExceptionHandler::class);

        $handler->report($e);

        $response = $handler->render($passable, $e);

        if (method_exists($response, 'withException')) {
            $response->withException($e);
        }

        return $response;
    }
}
