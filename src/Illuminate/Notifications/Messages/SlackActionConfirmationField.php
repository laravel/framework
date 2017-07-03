<?php

namespace Illuminate\Notifications\Messages;

class SlackActionConfirmationField
{
    /**
     * The title field of the confirmation field.
     *
     * @var string
     */
    protected $title;

    /**
     * The content of the confirmation field.
     *
     * @var string
     */
    protected $content;

    /**
     * The ok text of the confirmation field.
     *
     * @var string
     */
    protected $okText;

    /**
     * The dismiss text of the confirmation field.
     *
     * @var string
     */
    protected $dismissText;

    /**
     * Set the title of the field.
     *
     * @param  string  $title
     * @return $this
     */
    public function title($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Set the text of the field.
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
     * Set the text of the field.
     *
     * @param  string  $okText
     * @return $this
     */
    public function okText($okText)
    {
        $this->okText = $okText;

        return $this;
    }

    /**
     * Set the text of the field.
     *
     * @param  string  $dismissText
     * @return $this
     */
    public function dismissText($dismissText)
    {
        $this->dismissText = $dismissText;

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
            'title'        => $this->title,
            'text'         => $this->content,
            'ok_text'      => $this->okText,
            'dismiss_text' => $this->dismissText,
        ];
    }
}
