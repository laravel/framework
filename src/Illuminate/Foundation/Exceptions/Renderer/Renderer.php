<?php

namespace Illuminate\Foundation\Exceptions\Renderer;

use Illuminate\Contracts\View\Factory;
use Illuminate\Foundation\Exceptions\Renderer\Mappers\BladeMapper;
use Illuminate\Http\Request;
use Symfony\Component\ErrorHandler\ErrorRenderer\HtmlErrorRenderer;
use Throwable;

class Renderer
{
    /**
     * The view factory instance.
     *
     * @var \Illuminate\Contracts\View\Factory
     */
    protected $viewFactory;

    /**
     * The HTML error renderer instance.
     *
     * @var \Symfony\Component\ErrorHandler\ErrorRenderer\HtmlErrorRenderer
     */
    protected $htmlErrorRenderer;

    /**
     * The blade mapper instance.
     *
     * @var \Illuminate\Foundation\Exceptions\Renderer\Mappers\BladeMapper
     */
    protected $bladeMapper;

    /**
     * The application's base path.
     *
     * @var string
     */
    protected $basePath;

    /**
     * Creates a new exception renderer instance.
     *
     * @param  \Illuminate\Contracts\View\Factory  $viewFactory
     * @param  \Symfony\Component\ErrorHandler\ErrorRenderer\HtmlErrorRenderer  $htmlErrorRenderer
     * @param  \Illuminate\Foundation\Exceptions\Renderer\Mappers\BladeMapper  $bladeMapper
     * @param  string  $basePath
     * @return void
     */
    public function __construct(
        Factory $viewFactory,
        HtmlErrorRenderer $htmlErrorRenderer,
        BladeMapper $bladeMapper,
        string $basePath
    ) {
        $this->viewFactory = $viewFactory;
        $this->htmlErrorRenderer = $htmlErrorRenderer;
        $this->bladeMapper = $bladeMapper;
        $this->basePath = $basePath;
    }

    /**
     * Renders the given exception as HTML.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Throwable  $throwable
     * @return string
     */
    public function render(Request $request, Throwable $throwable)
    {
        $flattenException = $this->bladeMapper->map(
            $this->htmlErrorRenderer->render($throwable),
        );

        return $this->viewFactory->make('laravel-exceptions-renderer::show', [
            'exception' => new Exception($flattenException, $request, $this->basePath),
        ])->render();
    }
}
