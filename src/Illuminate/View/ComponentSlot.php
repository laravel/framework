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
     * The slot sanitizer.
     *
     * @var callable
     */
    protected $sanitizerResolver;


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

        // default sanitizer, return the input as it is
        $this->sanitizerResolver = fn($input) => $input;
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
     * Setup the sanitizer for the slot.
     *
     * @param  null|string|callable  $callable
     * @return $this
     */
    public function sanitize(null|string|callable $callable = null)
    {
        if (is_string($callable) && !function_exists($callable)) {
            throw new \InvalidArgumentException("Callable does not exist.");
        }

        $this->sanitizerResolver =
            $callable ??
            fn($input) => trim(preg_replace("/<!--([\s\S]*?)-->/", "", $input)); // replace everything between <!-- and --> with empty string

        return $this;
    }

    /**
     * Determine if the slot is empty.
     *
     * HTML comments and whitespace will be trimmed out.
     *
     * @return bool
     */
    public function isEmpty()
    {
        return filter_var($this->contents, FILTER_CALLBACK, [
          "options" => $this->sanitizerResolver
        ]) === '';
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
     * Get the slot's HTML string.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->toHtml();
    }
}
