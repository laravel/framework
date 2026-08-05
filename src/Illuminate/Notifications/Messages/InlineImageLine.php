<?php

namespace Illuminate\Notifications\Messages;

use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Mail\Message;
use Illuminate\Support\HtmlString;

class InlineImageLine
{
    /**
     * The text before the image.
     *
     * @var \Illuminate\Contracts\Support\Htmlable|string|null
     */
    public $before;

    /**
     * The inline image.
     *
     * @var \Illuminate\Notifications\Messages\InlineImage
     */
    public $image;

    /**
     * The text after the image.
     *
     * @var \Illuminate\Contracts\Support\Htmlable|string|null
     */
    public $after;

    /**
     * Create a new inline image line.
     *
     * @param  \Illuminate\Notifications\Messages\InlineImage  $image
     * @param  \Illuminate\Contracts\Support\Htmlable|string|null  $before
     * @param  \Illuminate\Contracts\Support\Htmlable|string|null  $after
     */
    public function __construct(InlineImage $image, $before = null, $after = null)
    {
        $this->before = $before;
        $this->image = $image;
        $this->after = $after;
    }

    /**
     * Render the line for an HTML mail message.
     *
     * @param  \Illuminate\Mail\Message  $message
     * @return \Illuminate\Support\HtmlString
     */
    public function toHtml(Message $message)
    {
        return new HtmlString(implode(' ', array_filter([
            $this->stringify($this->before),
            $this->image->toHtml($message)->toHtml(),
            $this->stringify($this->after),
        ], fn ($part) => $part !== null && $part !== '')));
    }

    /**
     * Get the plain text representation of the line.
     *
     * @return string
     */
    public function toText()
    {
        return trim(implode(' ', array_filter([
            $this->stringify($this->before, true),
            $this->image->toText(),
            $this->stringify($this->after, true),
        ], fn ($part) => $part !== null && $part !== '')));
    }

    /**
     * Convert a line part into a string.
     *
     * @param  \Illuminate\Contracts\Support\Htmlable|string|null  $part
     * @param  bool  $stripTags
     * @return string|null
     */
    protected function stringify($part, $stripTags = false)
    {
        if ($part instanceof Htmlable) {
            return $stripTags ? strip_tags($part->toHtml()) : $part->toHtml();
        }

        return $stripTags
            ? (! is_null($part) ? strip_tags($part) : null)
            : (! is_null($part) ? e($part) : null);
    }
}
