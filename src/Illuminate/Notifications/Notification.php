<?php

namespace Illuminate\Notifications;

use Illuminate\Queue\SerializesModels;

class Notification
{
    use SerializesModels;

    /**
     * The name of the application sending the notification.
     *
     * @var string
     */
    public $application;

    /**
     * The URL to the application's logo.
     *
     * @var string
     */
    public $logoUrl;

    /**
     * The "level" of the notification (info, success, error).
     *
     * @var string
     */
    public $level = 'info';

    /**
     * Specify the name of the application sending the notification.
     *
     * @param  string  $application
     * @param  string  $logoUrl
     * @return $this
     */
    public function application($application, $logoUrl = null)
    {
        $this->application = $application;
        $this->logoUrl = $logoUrl;

        return $this;
    }

    /**
     * Indicate that the notification gives information about a successful operation.
     *
     * @return $this
     */
    public function success()
    {
        return $this->level('success');
    }

    /**
     * Indicate that the notification gives information about an error.
     *
     * @return $this
     */
    public function error()
    {
        return $this->level('error');
    }

    /**
     * Set the "level" of the notification (success, error, etc.).
     *
     * @param  string  $level
     * @return $this
     */
    public function level($level)
    {
        $this->level = $level;

        return $this;
    }
}
