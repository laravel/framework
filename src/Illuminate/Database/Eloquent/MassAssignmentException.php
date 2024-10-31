<?php

namespace Illuminate\Database\Eloquent;

use RuntimeException;
use Throwable;

class MassAssignmentException extends RuntimeException
{
    /**
     * The affected keys.
     *
     * @var array
     */
    private array $keys;

    /**
     * The affected Eloquent model class.
     *
     * @var string
     */
    private string $class;

    /**
     * Create a new exception instance.
     *
     * @param  array|string  $keys
     * @param  string  $class
     * @param  int  $code
     * @param  \Throwable|null  $previous
     * @return void
     */
    public function __construct(array|string $keys, string $class, int $code = 0, ?Throwable $previous = null)
    {
        $keysCollection = collect($keys)->unique()->sort()->values();
        $this->keys = $keysCollection->all();
        $this->class = $class;

        $message = sprintf(
            "Add [%s] to fillable property to allow mass assignment on [%s].",
            $keysCollection->implode(', '), $class
        );

        parent::__construct($message, $code, $previous);
    }

    /**
     * Get the affected keys.
     *
     * @return array
     */
    public function getKeys(): array
    {
        return $this->keys;
    }

    /**
     * Get the affected Eloquent model class.
     *
     * @return string
     */
    public function getClass(): string
    {
        return $this->class;
    }
}
