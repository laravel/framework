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
     * The username to send the message from.
     *
     * @var string|null
     */
    public $username;

    /**
     * The user icon for the message.
     *
     * @var string|null
     */
    public $icon;

    /**
     * The channel to send the message on.
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
     * Additional request options for the Guzzle HTTP client.
     *
     * @var array
     */
    public $http = [];

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
     * Set a custom user icon for the Slack message.
     *
     * @param  string  $username
     * @param  string|null  $icon
     * @return $this
     */
    public function from($username, $icon = null)
    {
        $this->username = $username;

        if (! is_null($icon)) {
            $this->icon = $icon;
        }

        return $this;
    }

    /**
     * Set the Slack channel the message should be sent to.
     *
     * @param  string $channel
     * @return $this
     */
    public function to($channel)
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

    /**
     * Set additional request options for the Guzzle HTTP client.
     *
     * @param  array  $options
     * @return $this
     */
    public function http(array $options)
    {
        $this->http = $options;

        return $this;
    }
}
