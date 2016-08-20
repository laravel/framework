<?php

namespace Illuminate\Notifications\Messages;

use Closure;

class SlackMessage
{
    /**
     * The "level" of the notification (info, success, error).
     *
     * @var string
     */
    public $level = 'info';

    /**
     * Webhook username.
     *
     * @var string|null
     */
    public $username;

    /**
     * Webhook icon emoji.
     *
     * @var string|null
     */
    public $icon_emoji;

    /**
     * Channel override.
     *
     * @var string|null
     */
    public $channel;

    /**
     * The text content of the message.
     *
     * @var string
     */
    public $content;

    /**
     * The message's attachments.
     *
     * @var array
     */
    public $attachments = [];

    /**
     * Indicate that the notification gives information about a successful operation.
     *
     * @return $this
     */
    public function success()
    {
        $this->level = 'success';

        return $this;
    }

    /**
     * Indicate that the notification gives information about an error.
     *
     * @return $this
     */
    public function error()
    {
        $this->level = 'error';

        return $this;
    }

    /**
     * Set a custom username and emoji icon for the Slack message.
     *
     * @param  string       $username
     * @param  string|null  $icon
     * @return $this
     */
    public function as($username, $icon = null)
    {
        $this->username = $username;

        if (! is_null($icon)) {
            $this->icon_emoji = $icon;
        }

        return $this;
    }

    /**
     * Set which channel the Slack message should be posted in.
     *
     * @param  string $channel
     * @return $this
     */
    public function in($channel)
    {
        $this->channel = $channel;

        return $this;
    }

    /**
     * Set the content of the Slack message.
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
     * Define an attachment for the message.
     *
     * @param  \Closure  $callback
     * @return $this
     */
    public function attachment(Closure $callback)
    {
        $this->attachments[] = $attachment = new SlackAttachment;

        $callback($attachment);

        return $this;
    }

    /**
     * Get the color for the message.
     *
     * @return string
     */
    public function color()
    {
        switch ($this->level) {
            case 'success':
                return '#7CD197';
            case 'error':
                return '#F35A00';
        }
    }
}
