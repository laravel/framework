<?php

namespace Illuminate\View;

use Illuminate\Contracts\Support\Htmlable;

class ComponentSlot implements Htmlable
{
    /**
     * The slot attribute bag.
     *
     * @var \Illuminate\View\ComponentAttributeBag
     */
    public $attributes;

    /**
     * The slot contents.
     *
     * @var string
     */
    protected $contents;

    /**
     * Create a new slot instance.
     *
     * @param  string  $contents
     * @param  array  $attributes
     * @return void
     */
    public function __construct($contents = '', $attributes = [])
    {
        $this->contents = $contents;

        $this->withAttributes($attributes);
    }

    /**
     * Set the extra attributes that the slot should make available.
     *
     * @param  array  $attributes
     * @return $this
     */
    public function withAttributes(array $attributes)
    {
        $this->attributes = new ComponentAttributeBag($attributes);

        return $this;
    }

    /**
     * Get the slot's HTML string.
     *
     * @return string
     */
    public function toHtml()
    {
        return $this->contents;
    }

    /**
     * Determine if the slot is empty.
     *
     * @return bool
     */
    public function isEmpty()
    {
        return $this->contents === '';
    }

    /**
     * Determine if the slot is not empty.
     *
     * @return bool
     */
    public function isNotEmpty()
    {
        return ! $this->isEmpty();
    }

    /**
     * Determine if the slot is empty after being sanitized.
     *
     * @param  null|string|callable  $callable
     * @return bool
     */
    public function sanitizedEmpty(null|string|callable $callable = null)
    {
        if (is_string($callable) && ! function_exists($callable)) {
            throw new \InvalidArgumentException('Callable does not exist.');
        }

        $resolver =
            $callable ??
            fn ($input) => trim(preg_replace("/<!--([\s\S]*?)-->/", '', $input)); // replace everything between <!-- and --> with empty string

        return filter_var($this->contents, FILTER_CALLBACK, ['options' => $resolver,]) === '';
    }

    /**
     * Determine if the slot is not empty after being sanitized.
     *
     * @param  null|string|callable  $callable
     * @return bool
     */
    public function sanitizedNotEmpty(null|string|callable $callable = null)
    {
        return ! $this->sanitizedEmpty($callable);
    }

    /**
     * Get the slot's HTML string.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->toHtml();
    }
}
