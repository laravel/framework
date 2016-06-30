<?php

namespace Illuminate\Notifications;

use Illuminate\Support\Manager;
use Nexmo\Client as NexmoClient;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Bus\Dispatcher as Bus;
use Nexmo\Client\Credentials\Basic as NexmoCredentials;
use Illuminate\Contracts\Notifications\Factory as FactoryContract;
use Illuminate\Contracts\Notifications\Dispatcher as DispatcherContract;

class ChannelManager extends Manager implements DispatcherContract, FactoryContract
{
    /**
     * The default channels used to deliver messages.
     *
     * @var array
     */
    protected $defaultChannels = ['mail', 'database'];

    /**
     * Create a new notification for the given notifiable entities.
     *
     * @param  array  $notifiables
     * @return \Illuminate\Notifications\Notification
     */
    public function to($notifiables)
    {
        return new Channels\Notification($this, $notifiables);
    }

    /**
     * Dispatch the given notification instance to the given notifiable.
     *
     * @param  mixed  $notifiable
     * @param  mixed  $instance
     * @param  array  $channels
     * @return void
     */
    public function dispatch($notifiable, $instance, array $channels = [])
    {
        $notifications = $this->notificationsFromInstance(
            $notifiable, $instance
        );

        if (count($channels) > 0) {
            foreach ($notifications as $notification) {
                $notification->via((array) $channels);
            }
        }

        if ($instance instanceof ShouldQueue) {
            return $this->queueNotifications($instance, $notifications);
        }

        foreach ($notifications as $notification) {
            $this->send($notification);
        }
    }

    /**
     * Queue the given notification instances.
     *
     * @param  mixed  $instance
     * @param  array[\Illuminate\Notifcations\Channels\Notification]
     * @return void
     */
    protected function queueNotifications($instance, array $notifications)
    {
        $this->app->make(Bus::class)->dispatch(
            (new SendQueuedNotifications($notifications))
                    ->onConnection($instance->connection)
                    ->onQueue($instance->queue)
                    ->delay($instance->delay)
        );
    }

    /**
     * Send the given notification.
     *
     * @param  \Illuminate\Notifications\Channels\Notification  $notification
     * @return void
     */
    public function send(Channels\Notification $notification)
    {
        if (! $notification->application) {
            $notification->application(
                $this->app['config']['app.name'],
                $this->app['config']['app.logo']
            );
        }

        foreach ($notification->via ?: $this->deliversVia() as $channel) {
            $this->driver($channel)->send($notification);
        }

        $this->app->make('events')->fire(
            new Events\NotificationSent($notification)
        );
    }

    /**
     * Get a channel instance.
     *
     * @param  string  $driver
     * @return mixed
     */
    public function channel($name = null)
    {
        return $this->driver($name);
    }

    /**
     * Create an instance of the database driver.
     *
     * @return \Illuminate\Notifications\Channels\DatabaseChannel
     */
    protected function createDatabaseDriver()
    {
        return $this->app->make(Channels\DatabaseChannel::class);
    }

    /**
     * Create an instance of the mail driver.
     *
     * @return \Illuminate\Notifications\Channels\MailChannel
     */
    protected function createMailDriver()
    {
        return $this->app->make(Channels\MailChannel::class);
    }

    /**
     * Create an instance of the Nexmo driver.
     *
     * @return \Illuminate\Notifications\Channels\NexmoSmsChannel
     */
    protected function createNexmoDriver()
    {
        return new Channels\NexmoSmsChannel(
            new NexmoClient(new NexmoCredentials(
                $this->app['config']['services.nexmo.key'],
                $this->app['config']['services.nexmo.secret']
            )),
            $this->app['config']['services.nexmo.sms_from']
        );
    }

    /**
     * Create an instance of the Slack driver.
     *
     * @return \Illuminate\Notifications\Channels\SlackChannel
     */
    protected function createSlackDriver()
    {
        return $this->app->make(Channels\SlackWebhookChannel::class);
    }

    /**
     * Get the default channel driver names.
     *
     * @return array
     */
    public function getDefaultDriver()
    {
        return $this->defaultChannels;
    }

    /**
     * Get the default channel driver names.
     *
     * @return array
     */
    public function deliversVia()
    {
        return $this->getDefaultDriver();
    }

    /**
     * Set the default channel driver names.
     *
     * @param  array|string  $channels
     * @return void
     */
    public function deliverVia($channels)
    {
        $this->defaultChannels = (array) $channels;
    }

    /**
     * Build a new channel notification from the given object.
     *
     * @param  mixed  $notifiable
     * @param  mixed  $notification
     * @param  array|null  $channels
     * @return array
     */
    public function notificationsFromInstance($notifiable, $notification, $channels = null)
    {
        return Channels\Notification::notificationsFromInstance($notifiable, $notification, $channels);
    }
}
