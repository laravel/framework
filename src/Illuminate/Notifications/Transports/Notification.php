<?php

namespace Illuminate\Notifications\Transports;

use Illuminate\Support\Str;
use Illuminate\Support\Collection;
use Illuminate\Container\Container;
use Illuminate\Notifications\Action;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Notifications\TransportManager;

class Notification implements Arrayable
{
    /**
     * The entities that should receive the notification.
     *
     * @var \Illuminate\Support\Collection
     */
    public $notifiables;

    /**
     * The transports that the notification should be sent through.
     */
    public $via = [];

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
     * The subject of the notification.
     *
     * @var string
     */
    public $subject;

    /**
     * The "intro" lines of the notification.
     *
     * @var array
     */
    public $introLines = [];

    /**
     * The "outro" lines of the notification.
     *
     * @var array
     */
    public $outroLines = [];

    /**
     * The text / label for the action.
     *
     * @var string
     */
    public $actionText;

    /**
     * The action URL.
     *
     * @var string
     */
    public $actionUrl;

    /**
     * Create a new notification instance.
     *
     * @param  array  $notifiables
     * @return void
     */
    public function __construct($notifiables)
    {
        $this->notifiables = Collection::make($notifiables);
    }

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

    /**
     * Set the subject of the notification.
     *
     * @param  string  $subject
     * @return $this
     */
    public function subject($subject)
    {
        $this->subject = $subject;

        return $this;
    }

    /**
     * Add a line of text to the notification.
     *
     * @param  \Illuminate\Notifications\Action|string  $line
     * @return $this
     */
    public function line($line)
    {
        return $this->with($line);
    }

    /**
     * Add a line of text to the notification.
     *
     * @param  \Illuminate\Notifications\Action|string  $line
     * @return $this
     */
    public function with($line)
    {
        if ($line instanceof Action) {
            $this->action($line->text, $line->url);
        } elseif (! $this->actionText) {
            $this->introLines[] = trim($line);
        } else {
            $this->outroLines[] = trim($line);
        }

        return $this;
    }

    /**
     * Configure the "call to action" button.
     *
     * @param  string  $text
     * @param  string  $url
     * @return $this
     */
    public function action($text, $url)
    {
        $this->actionText = $text;
        $this->actionUrl = $url;

        return $this;
    }

    /**
     * Set the transports that should be used to deliver the notification.
     *
     * @param  array|string  $transports
     * @return $this
     */
    public function via($transports)
    {
        $this->via = (array) $transports;

        return $this;
    }

    /**
     * Send the notification.
     *
     * @return void
     */
    public function send()
    {
        return Container::getInstance(TransportManager::class)->send($this);
    }

    /**
     * Build a new transport notification from the given object.
     *
     * @param  mixed  $notifiable
     * @param  mixed  $instance
     * @param  array|null  $transports
     * @return array[static]
     */
    public static function notificationsFromInstance($notifiable, $instance, $transports = null)
    {
        $notifications = [];

        $transports = $transports ?: $instance->via($notifiable);

        foreach ($transports as $transport) {
            $notifications[] = $notification = new static([$notifiable]);

            $notification->via($transport)
                         ->subject($instance->subject())
                         ->level($instance->level());

            $method = static::messageMethod($instance, $transport);

            foreach ($instance->{$method}()->elements as $element) {
                $notification->with($element);
            }
        }

        return $notifications;
    }

    /**
     * Get the proper message method for the given instance and transport.
     *
     * @param  mixed  $instance
     * @param  string  $transport
     * @return string
     */
    protected static function messageMethod($instance, $transport)
    {
        return method_exists(
            $instance, $transportMethod = Str::camel($transport).'Message'
        ) ? $transportMethod : 'message';
    }

    /**
     * Get the instance as an array.
     *
     * @return array
     */
    public function toArray()
    {
        return [
            'notifiables' => $this->notifiables,
            'application' => $this->application,
            'logoUrl' => $this->logoUrl,
            'level' => $this->level,
            'subject' => $this->subject,
            'introLines' => $this->introLines,
            'outroLines' => $this->outroLines,
            'actionText' => $this->actionText,
            'actionUrl' => $this->actionUrl,
        ];
    }
}
