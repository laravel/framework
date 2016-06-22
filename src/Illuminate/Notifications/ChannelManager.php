<?php

namespace Illuminate\Notifications;

use Illuminate\Support\Manager;
use Nexmo\Client as NexmoClient;
use Nexmo\Client\Credentials\Basic as NexmoCredentials;
use Illuminate\Contracts\Notifications\Factory as FactoryContract;

class ChannelManager extends Manager implements FactoryContract
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
}
