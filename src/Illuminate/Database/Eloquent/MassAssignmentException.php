<?php

namespace Illuminate\Database\Eloquent;

use RuntimeException;

class MassAssignmentException extends RuntimeException
{
    /**
     * The affected keys.
     *
     * @var array
     */
    protected array $keys;

    /**
     * The affected Eloquent model class.
     *
     * @var string
     */
    protected string $class;

    /**
     * Create a new exception instance.
     *
     * @param  array|string  $keys
     * @param  object  $model
     * @return static
     */
    public static function make(array|string $keys, object $model): static
    {
        $properties = collect($keys)->unique()->sort()->values();

        $instance = new static;
        $instance->keys = $properties->all();
        $instance->class = $model::class;
        $instance->message = sprintf(
            'Add [%s] to fillable property to allow mass assignment on [%s].',
            $properties->implode(', '), $model::class
        );

        return $instance;
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
