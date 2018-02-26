<?php

namespace Illuminate\Notifications\Messages;

use Illuminate\Support\InteractsWithTime;

class SlackAttachment
{
    use InteractsWithTime;

    /**
     * The attachment's title.
     *
     * @var string
     */
    public $title;

    /**
     * The attachment's URL.
     *
     * @var string
     */
    public $url;

    /**
     * The attachment's text content.
     *
     * @var string
     */
    public $content;

    /**
     * A plain-text summary of the attachment.
     *
     * @var string
     */
    public $fallback;

    /**
     * The attachment's color.
     *
     * @var string
     */
    public $color;

    /**
     * The attachment's fields.
     *
     * @var array
     */
    public $fields;

    /**
     * The fields containing markdown.
     *
     * @var array
     */
    public $markdown;

    /**
     * The attachment's image url.
     *
     * @var string
     */
    public $imageUrl;

    /**
     * The attachment's thumb url.
     *
     * @var string
     */
    public $thumbUrl;

    /**
     * The attachment author's name.
     *
     * @var string
     */
    public $authorName;

    /**
     * The attachment author's link.
     *
     * @var string
     */
    public $authorLink;

    /**
     * The attachment author's icon.
     *
     * @var string
     */
    public $authorIcon;

    /**
     * The attachment's footer.
     *
     * @var string
     */
    public $footer;

    /**
     * The attachment's footer icon.
     *
     * @var string
     */
    public $footerIcon;

    /**
     * The attachment's timestamp.
     *
     * @var int
     */
    public $timestamp;

    /**
     * Set the title of the attachment.
     *
     * @param  string  $title
     * @param  string|null  $url
     * @return $this
     */
    public function title($title, $url = null)
    {
        $this->title = $title;
        $this->url = $url;

        return $this;
    }

    /**
     * Set the content (text) of the attachment.
     *
     * @param  string  $content
     * @return $this
     */
    public function content($content)
    {
        $this->content = $content;

        return $this;
    }

    /**
     * A plain-text summary of the attachment.
     *
     * @param  string  $fallback
     * @return $this
     */
    public function fallback($fallback)
    {
        $this->fallback = $fallback;

        return $this;
    }

    /**
     * Set the color of the attachment.
     *
     * @param  string  $color
     * @return $this
     */
    public function color($color)
    {
        $this->color = $color;

        return $this;
    }

    /**
     * Add a field to the attachment.
     *
     * @param  \Closure|string $title
     * @param  string $content
     * @return $this
     */
    public function field($title, $content = '')
    {
        if (is_callable($title)) {
            $callback = $title;

            $callback($attachmentField = new SlackAttachmentField);

            $this->fields[] = $attachmentField;

            return $this;
        }

        $this->fields[$title] = $content;

        return $this;
    }

    /**
     * Set the fields of the attachment.
     *
     * @param  array  $fields
     * @return $this
     */
    public function fields(array $fields)
    {
        $this->fields = $fields;

        return $this;
    }

    /**
     * Set the fields containing markdown.
     *
     * @param  array  $fields
     * @return $this
     */
    public function markdown(array $fields)
    {
        $this->markdown = $fields;

        return $this;
    }

    /**
     * Set the image URL.
     *
     * @param  string  $url
     * @return $this
     */
    public function image($url)
    {
        $this->imageUrl = $url;

        return $this;
    }

    /**
     * Set the URL to the attachment thumbnail.
     *
     * @param  string  $url
     * @return $this
     */
    public function thumb($url)
    {
        $this->thumbUrl = $url;

        return $this;
    }

    /**
     * Set the author of the attachment.
     *
     * @param  string  $name
     * @param  string|null  $link
     * @param  string|null  $icon
     * @return $this
     */
    public function author($name, $link = null, $icon = null)
    {
        $this->authorName = $name;
        $this->authorLink = $link;
        $this->authorIcon = $icon;

        return $this;
    }

    /**
     * Set the footer content.
     *
     * @param  string  $footer
     * @return $this
     */
    public function footer($footer)
    {
        $this->footer = $footer;

        return $this;
    }

    /**
     * Set the footer icon.
     *
     * @param  string $icon
     * @return $this
     */
    public function footerIcon($icon)
    {
        $this->footerIcon = $icon;

        return $this;
    }

    /**
     * Set the timestamp.
     *
     * @param  \DateTimeInterface|\DateInterval|int  $timestamp
     * @return $this
     */
    public function timestamp($timestamp)
    {
        $this->timestamp = $this->availableAt($timestamp);

        return $this;
    }
}
