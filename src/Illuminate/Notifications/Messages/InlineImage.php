<?php

namespace Illuminate\Notifications\Messages;

use Illuminate\Contracts\Mail\Attachable;
use Illuminate\Mail\Attachment;
use Illuminate\Mail\Message;
use Illuminate\Support\HtmlString;
use InvalidArgumentException;

class InlineImage
{
    /**
     * The attachment that should be embedded.
     *
     * @var \Illuminate\Mail\Attachment
     */
    public $attachment;

    /**
     * The alternative text for the image.
     *
     * @var string|null
     */
    public $alt;

    /**
     * The image width.
     *
     * @var int|string|null
     */
    public $width;

    /**
     * The image height.
     *
     * @var int|string|null
     */
    public $height;

    /**
     * The image style.
     *
     * @var string|null
     */
    public $style;

    /**
     * Create a new inline image.
     *
     * @param  string|\Illuminate\Contracts\Mail\Attachable|\Illuminate\Mail\Attachment  $image
     * @param  string|null  $alt
     * @param  int|string|null  $width
     * @param  int|string|null  $height
     * @param  string|null  $style
     *
     * @throws \InvalidArgumentException
     */
    public function __construct($image, $alt = '', $width = null, $height = null, $style = null)
    {
        if ($image instanceof Attachable) {
            $image = $image->toMailAttachment();
        }

        if (is_string($image)) {
            $image = Attachment::fromPath($image);
        }

        if (! $image instanceof Attachment) {
            throw new InvalidArgumentException('Inline images must be a path, an attachment, or an attachable instance.');
        }

        $this->attachment = $image;
        $this->alt = $alt;
        $this->width = $width;
        $this->height = $height;
        $this->style = $style;
    }

    /**
     * Render the image for an HTML mail message.
     *
     * @param  \Illuminate\Mail\Message  $message
     * @return \Illuminate\Support\HtmlString
     */
    public function toHtml(Message $message)
    {
        $attributes = [
            'src' => $message->embed($this->attachment),
            'alt' => $this->alt ?? '',
        ];

        if (! is_null($this->width)) {
            $attributes['width'] = $this->width;
        }

        if (! is_null($this->height)) {
            $attributes['height'] = $this->height;
        }

        if (! is_null($this->style)) {
            $attributes['style'] = $this->style;
        }

        if (is_null($this->alt)) {
            $attributes['aria-hidden'] = 'true';
        }

        $attributes = array_map(
            fn ($attribute, $value) => $attribute.'="'.htmlspecialchars(
                $value,
                ENT_QUOTES | ENT_SUBSTITUTE,
                'UTF-8',
            ).'"',
            array_keys($attributes),
            $attributes,
        );

        return new HtmlString('<img '.implode(' ', $attributes).'>');
    }

    /**
     * Get the plain text representation of the image.
     *
     * @return string
     */
    public function toText()
    {
        return $this->alt ?? '';
    }
}
