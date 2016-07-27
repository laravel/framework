<?php

namespace Illuminate\Notifications;

use Illuminate\Support\Str;

class Notification
{
    /**
     * The notification priority level.
     *
     * @var string
     */
    protected $level = 'info';

    /**
     * The notification's subject.
     *
     * @var string|null
     */
    protected $subject;

    /**
     * Get the "level" of the notification.
     *
     * @return string
     */
    public function level()
    {
        return $this->level;
    }

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
     * Get or set the subject of the notification.
     *
     * @param  string|null  $subject
     * @return string
     */
    public function subject($subject = null)
    {
        if (is_null($subject)) {
            return $this->subject ?: Str::title(Str::snake(class_basename($this), ' '));
        }

        $this->subject = $subject;

        return $this;
    }

    /**
     * Create a new message builder instance.
     *
     * @param  string  $line
     * @return \Illuminate\Notifications\MessageBuilder
     */
    public function line($line)
    {
        return new MessageBuilder($this, $line);
    }

    /**
     * Get the notification's options.
     *
     * @return array
     */
    public function options()
    {
        return property_exists($this, 'options') ? $this->options : [];
    }
}
