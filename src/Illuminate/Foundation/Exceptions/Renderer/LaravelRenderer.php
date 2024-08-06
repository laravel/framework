<?php

namespace Illuminate\Foundation\Exceptions\Renderer;

use Illuminate\Contracts\Foundation\ExceptionRenderer;
use Illuminate\Http\Request;

class LaravelRenderer implements ExceptionRenderer
{
    /**
     * The renderer instance.
     *
     * @var \Illuminate\Foundation\Exceptions\Renderer\Renderer
     */
    protected $renderer;

    /**
     * The request instance.
     *
     * @var \Illuminate\Http\Request
     */
    protected $request;

    /**
     * Create a new default Laravel exception renderer instance.
     *
     * @param  \Illuminate\Foundation\Exceptions\Renderer\Renderer  $renderer
     * @param  \Illuminate\Http\Request  $request
     */
    public function __construct(Renderer $renderer, Request $request)
    {
        $this->renderer = $renderer;
        $this->request = $request;
    }

    /**
     * Renders the given exception as HTML.
     *
     * @param  \Throwable  $throwable
     * @return string
     */
    public function render($throwable)
    {
        return $this->renderer->render($this->request, $throwable);
    }

    /**
     * Get the request instance.
     *
     * @return \Illuminate\Http\Request
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * Set the request instance.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return $this
     */
    public function setRequest(Request $request)
    {
        $this->request = $request;

        return $this;
    }
}
