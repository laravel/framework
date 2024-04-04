<?php

namespace Illuminate\Foundation\Exceptions\Renderer;

use Illuminate\Foundation\Exceptions\Renderer\Mappers\BladeMapper;
use Illuminate\Http\Request;
use Symfony\Component\ErrorHandler\Exception\FlattenException;

class Exception
{
    /**
     * The "flatten" exception instance.
     *
     * @var \Symfony\Component\ErrorHandler\Exception\FlattenException
     */
    protected $exception;

    /**
     * The application's base path.
     *
     * @var string
     */
    protected $basePath;

    /**
     * The current request instance.
     *
     * @var \Illuminate\Http\Request
     */
    protected $request;

    /**
     * The mappers to apply to the exception.
     *
     * @var array<int, class-string>
     */
    protected $mappers = [
        BladeMapper::class,
    ];

    /**
     * Creates a new exception renderer instance.
     *
     * @param  \Symfony\Component\ErrorHandler\Exception\FlattenException  $exception
     * @param  string  $basePath
     * @return void
     */
    public function __construct(FlattenException $exception, Request $request, string $basePath)
    {
        $this->exception = $exception;
        $this->request = $request;
        $this->basePath = $basePath;
    }

    /**
     * Get the request method.
     *
     * @return string
     */
    public function requestMethod()
    {
        return $this->request->method();
    }

    /**
     * Get the request URI.
     *
     * @return string
     */
    public function requestUri()
    {
        return $this->request->path();
    }

    /**
     * Get the exception title.
     *
     * @return int
     */
    public function title()
    {
        return $this->exception->getStatusText();
    }

    /**
     * Get the exception message.
     *
     * @return string
     */
    public function message()
    {
        return $this->exception->getMessage();
    }

    /**
     * Get the exception class name.
     *
     * @return string
     */
    public function class()
    {
        return $this->exception->getClass();
    }

    /**
     * Get the first "non-vendor" frame index.
     *
     * @return int
     */
    public function defaultFrame()
    {
        $key = array_search(false, array_map(function (Frame $frame) {
            return $frame->isFromVendor();
        }, $this->frames()->all()));

        return $key === false ? 0 : $key;
    }

    /**
     * Get the exception message trace.
     *
     * @return \Illuminate\Support\Collection<int, Frame>
     */
    public function frames()
    {
        return collect(array_map(function (array $trace) {
            return new Frame($this->exception, $trace, $this->basePath);
        }, $this->exception->getTrace()));
    }
}
