<?php

namespace Illuminate\Notifications\Messages;

use Carbon\Carbon;

class SlackAttachment
{
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
     * @param  string  $url
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
     * @param  \Closure|array $title
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
     * @param  Carbon  $timestamp
     * @return $this
     */
    public function timestamp(Carbon $timestamp)
    {
        $this->timestamp = $timestamp->getTimestamp();

        return $this;
    }
}
