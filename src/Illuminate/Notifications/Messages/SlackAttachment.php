<?php

namespace Illuminate\Notifications\Messages;

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
}
