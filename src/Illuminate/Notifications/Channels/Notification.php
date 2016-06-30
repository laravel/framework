<?php

namespace Illuminate\Notifications\Channels;

use Illuminate\Support\Str;
use Illuminate\Container\Container;
use Illuminate\Notifications\Action;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Notifications\ChannelManager;
use Illuminate\Database\Eloquent\Collection;

class Notification implements Arrayable
{
    use SerializesModels;

    /**
     * The entities that should receive the notification.
     *
     * @var \Illuminate\Support\Collection
     */
    public $notifiables;

    /**
     * The channels that the notification should be sent through.
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
     * Set the channels that should be used to deliver the notification.
     *
     * @param  array|string  $channels
     * @return $this
     */
    public function via($channels)
    {
        $this->via = (array) $channels;

        return $this;
    }

    /**
     * Send the notification.
     *
     * @return void
     */
    public function send()
    {
        return Container::getInstance(ChannelManager::class)->send($this);
    }

    /**
     * Build a new channel notification from the given object.
     *
     * @param  mixed  $notifiable
     * @param  mixed  $instance
     * @param  array|null  $channels
     * @return array[static]
     */
    public static function notificationsFromInstance($notifiable, $instance, $channels = null)
    {
        $notifications = [];

        $channels = $channels ?: $instance->via($notifiable);

        $channels = $channels ?: app(ChannelManager::class)->deliversVia();

        foreach ($channels as $channel) {
            $notifications[] = $notification = new static([$notifiable]);

            $notification->via($channel)
                         ->subject($instance->subject())
                         ->level($instance->level());

            $method = static::messageMethod($instance, $channel);

            foreach ($instance->{$method}($notifiable)->elements as $element) {
                $notification->with($element);
            }
        }

        return $notifications;
    }

    /**
     * Get the proper message method for the given instance and channel.
     *
     * @param  mixed  $instance
     * @param  string  $channel
     * @return string
     */
    protected static function messageMethod($instance, $channel)
    {
        return method_exists(
            $instance, $channelMethod = Str::camel($channel).'Message'
        ) ? $channelMethod : 'message';
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
