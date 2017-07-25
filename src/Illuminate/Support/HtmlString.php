<?php

namespace Illuminate\Support;

use JsonSerializable;
use Illuminate\Contracts\Support\Htmlable;

class HtmlString implements Htmlable, JsonSerializable
{
    /**
     * The HTML string.
     *
     * @var string
     */
    protected $html;

    /**
     * Create a new HTML string instance.
     *
     * @param  string  $html
     * @return void
     */
    public function __construct($html)
    {
        $this->html = $html;
    }

    /**
     * Get the HTML string.
     *
     * @return string
     */
    public function toHtml()
    {
        return $this->html;
    }

    /**
     * Convert the object into something JSON serializable.
     *
     * @return string
     */
    public function jsonSerialize()
    {
        return $this->toHtml();
    }

    /**
     * Get the HTML string.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->toHtml();
    }
}
