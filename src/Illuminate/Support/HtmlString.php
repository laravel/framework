<?php

namespace Illuminate\Support;

use Illuminate\Contracts\Support\Htmlable;

class HtmlString implements Htmlable
{
    /**
     * The underlying string value.
     *
     * @var string
     */
    protected $value;

    /**
     * Create a new HTML string instance.
     *
     * @param  string  $value
     * @return void
     */
    public function __construct($value = '')
    {
        $this->value = (string) $value;
    }

    /**
     * Get the raw string value.
     *
     * @return string
     */
    public function toHtml()
    {
        return (string) $this->value;
    }

    /**
     * Get the raw string value.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->toHtml();
    }
}
