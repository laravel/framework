<?php

namespace Illuminate\Notifications\Messages;

class SlackActionConfirmationField
{
    /**
     * The title field of the pop up confirmation window.
     *
     * @var string
     */
    protected $title;

    /**
     * The details of the consequences of performing an action.
     *
     * @var string
     */
    protected $content;

    /**
     * The text label for the button to continue with an action.
     *
     * @var string
     */
    protected $okText = 'Okay';

    /**
     * The text label for the button to cancel the action.
     *
     * @var string
     */
    protected $dismissText = 'Cancel';

    /**
     * Set the title of the confirmation window.
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
     * Set the content of the confirmation window.
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
     * Set the text of the okay button.
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
     * Set the text of the dismiss button.
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
     * Get the array representation of the confirmation window.
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
