<?php

namespace Illuminate\Foundation\Exceptions\Renderer;

use ReflectionClass;
use Symfony\Component\ErrorHandler\Exception\FlattenException;

class Frame
{
    /**
     * The "flatten" exception instance.
     *
     * @var \Symfony\Component\ErrorHandler\Exception\FlattenException
     */
    protected $exception;

    /**
     * The frame's raw data from the "flatten" exception.
     *
     * @var array{file: string, line: int, class?: string, type?: string, function?: string}
     */
    protected $frame;

    /**
     * The application's base path.
     *
     * @var string
     */
    protected $basePath;

    /**
     * Create a new trace frame instance.
     *
     * @param  \Symfony\Component\ErrorHandler\Exception\FlattenException  $exception
     * @param  array{file: string, line: int, class?: string, type?: string, function?: string}
     * @param  string  $basePath
     * @return void
     */
    public function __construct(FlattenException $exception, array $frame, string $basePath)
    {
        $this->exception = $exception;
        $this->frame = $frame;
        $this->basePath = $basePath;
    }

    /**
     * Get the frame's source.
     *
     * @return string
     */
    public function source()
    {
        return match (true) {
            ! empty($this->frame['class']) => value(function () {
                $reflector = new ReflectionClass($this->frame['class']);

                if ($reflector->getFileName() === realpath($this->frame['file'])) {
                    return $this->frame['class'];
                }

                return str_replace($this->basePath.'/', '', $this->frame['file']);
            }),
            default => str_replace($this->basePath.'/', '', $this->frame['file']),
        };
    }

    /**
     * Get the frame file.
     *
     * @return string
     */
    public function file()
    {
        return $this->frame['file'];
    }

    /**
     * Get the frame line.
     *
     * @return int
     */
    public function line()
    {
        return $this->frame['line'];
    }

    /**
     * Get the frame function.
     *
     * @return string
     */
    public function callable()
    {
        return match (true) {
            ! empty($this->frame['function']) => $this->frame['function'],
            default => 'throw',
        };
    }

    /**
     * Get the frame snippet.
     *
     * @return string
     */
    public function snippet()
    {
        $contents = file($this->frame['file']) ?: [];

        $start = max($this->frame['line'] - 12, 0);

        $length = 12 * 2 + 1;

        return implode('', array_slice($contents, $start, $length));
    }

    /**
     * Determine if the frame is from a vendor package.
     *
     * @return bool
     */
    public function isFromVendor()
    {
        return ! str_starts_with($this->frame['file'], $this->basePath)
            || str_starts_with($this->frame['file'], $this->basePath.'/vendor');
    }
}
