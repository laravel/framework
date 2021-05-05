<?php

namespace Illuminate\Pagination;

use Illuminate\Contracts\Support\Arrayable;
use UnexpectedValueException;

class Cursor implements Arrayable
{
    /**
     * The parameters associated with the cursor.
     *
     * @var array
     */
    protected $parameters;

    /**
     * Determine whether the cursor points to the next or previous set of items.
     *
     * @var bool
     */
    protected $isNext;

    /**
     * Create a new cursor instance.
     *
     * @param  array  $parameters
     * @param  bool  $isNext
     */
    public function __construct(array $parameters, $isNext = true)
    {
        $this->parameters = $parameters;
        $this->isNext = $isNext;
    }

    /**
     * Get the given parameter from the cursor.
     *
     * @param  string  $parameterName
     * @return string|null
     */
    public function getParam(string $parameterName)
    {
        if (! isset($this->parameters[$parameterName])) {
            throw new UnexpectedValueException("Unable to find parameter [{$parameterName}] in pagination item.");
        }

        return $this->parameters[$parameterName];
    }

    /**
     * Get the given parameters from the cursor.
     *
     * @param  array  $parameterNames
     * @return array
     */
    public function getParams(array $parameterNames)
    {
        return collect($parameterNames)->map(function ($parameterName) {
            return $this->getParam($parameterName);
        })->toArray();
    }

    /**
     * Determine whether the cursor points to the next set of items.
     *
     * @return bool
     */
    public function isNext()
    {
        return $this->isNext;
    }

    /**
     * Determine whether the cursor points to the previous set of items.
     *
     * @return bool
     */
    public function isPrev()
    {
        return ! $this->isNext;
    }

    /**
     * Set the cursor to point to the next set of items.
     *
     * @return $this
     */
    public function setNext()
    {
        $this->isNext = true;

        return $this;
    }

    /**
     * Set the cursor to point to the previous set of items.
     *
     * @return $this
     */
    public function setPrev()
    {
        $this->isNext = false;

        return $this;
    }

    /**
     * Get the array representation of the cursor.
     *
     * @return array
     */
    public function toArray()
    {
        return array_merge($this->parameters, [
            '_isNext' => $this->isNext,
        ]);
    }

    /**
     * Get the encoded string representation of the cursor to construct a URL.
     *
     * @return string
     */
    public function encode()
    {
        return str_replace(['+', '/', '='], ['-', '_', ''], base64_encode(json_encode($this->toArray())));
    }

    /**
     * Get a cursor instance from the encoded string representation.
     *
     * @param  string|null  $encodedString
     * @return static|null
     */
    public static function fromEncoded($encodedString)
    {
        if (is_null($encodedString) || ! is_string($encodedString)) {
            return null;
        }

        $parameters = json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], $encodedString)), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return null;
        }

        $isNext = $parameters['_isNext'];

        unset($parameters['_isNext']);

        return new static($parameters, $isNext);
    }
}
