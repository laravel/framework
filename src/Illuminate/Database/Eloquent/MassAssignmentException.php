<?php

namespace Illuminate\Database\Eloquent;

use RuntimeException;
use Throwable;

class MassAssignmentException extends RuntimeException
{
    /**
     * The affected key.
     *
     * @var string
     */
    private string $key;

    /**
     * The affected Eloquent model class.
     *
     * @var string
     */
    private string $class;

    /**
     * Create a new exception instance.
     *
     * @param  string  $key
     * @param  string  $class
     * @param  int  $code
     * @param  \Throwable|null  $previous
     *
     */
    public function __construct(string $key, string $class, int $code = 0, ?Throwable $previous = null)
    {
        $this->key = $key;
        $this->class = $class;

        $message = "Add [{$key}] to fillable property to allow mass assignment on [{$class}].";

        parent::__construct($message, $code, $previous);
    }

    /**
     * Get the affected key.
     *
     * @return string
     */
    public function getKey(): string
    {
        return $this->key;
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
