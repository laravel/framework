<?php

namespace Illuminate\Foundation\Exceptions;

use Illuminate\Contracts\Debug\ExceptionRenderer;

abstract class Renderer implements ExceptionRenderer
{
    /**
     * Request.
     *
     * @var
     */
    protected $request;

    /**
     * Exception to be rendered.
     *
     * @var \Exception
     */
    protected $exception;

    /**
     * Renderer constructor.
     *
     * @param $request
     * @param \Exception $exception
     */
    public function __construct($request, \Exception $exception)
    {
        $this->request = $request;
        $this->exception = $exception;
    }
}
