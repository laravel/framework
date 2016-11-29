<?php

namespace Illuminate\Notifications\Messages;

class SlackAttachmentField
{
    /**
     * The title field of the attachment field.
     *
     * @var string
     */
    protected $title;

    /**
     * The content of the attachment field.
     *
     * @var string
     */
    protected $content;

    /**
     * Whether the content is short.
     *
     * @var bool
     */
    protected $short = true;

    /**
     * Set the title of the field.
     *
     * @param  string $title
     * @return $this
     */
    public function title($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Set the content of the field.
     *
     * @param  string $content
     * @return $this
     */
    public function content($content)
    {
        $this->content = $content;

        return $this;
    }

    /**
     * Indicates that the content should not be displayed side-by-side with other fields.
     *
     * @return $this
     */
    public function long()
    {
        $this->short = false;

        return $this;
    }

    /**
     * Get the array representation of the attachment field.
     *
     * @return array
     */
    public function toArray()
    {
        return [
            'title' => $this->title,
            'value' => $this->content,
            'short' => $this->short,
        ];
    }
}
