<?php

namespace Illuminate\Notifications;

use Illuminate\Support\Manager;
use Nexmo\Client as NexmoClient;
use Nexmo\Client\Credentials\Basic as NexmoCredentials;

class TransportManager extends Manager
{
    /**
     * Create a new notification for the given notifiable entities.
     *
     * @param  array  $notifiables
     * @return \Illuminate\Notifications\Notification
     */
    public function to($notifiables)
    {
        return (new Transports\Notification($this, $notifiables))->application(
            $this->app['config']['app.name'],
            $this->app['config']['app.logo']
        );
    }

    /**
     * Send the given notification.
     *
     * @param  \Illuminate\Notifications\Transports\Notification  $notification
     * @return void
     */
    public function send(Transports\Notification $notification)
    {
        foreach ($notification->via as $transport) {
            $this->driver($transport)->send($notification);
        }

        $this->app->make('events')->fire(
            new Events\NotificationSent($notification)
        );
    }

    /**
     * Create an instance of the database driver.
     *
     * @return \Illuminate\Notifications\Transports\DatabaseTransport
     */
    protected function createDatabaseDriver()
    {
        return $this->app->make(Transports\DatabaseTransport::class);
    }

    /**
     * Create an instance of the mail driver.
     *
     * @return \Illuminate\Notifications\Transports\MailTransport
     */
    protected function createMailDriver()
    {
        return $this->app->make(Transports\MailTransport::class);
    }

    /**
     * Create an instance of the Nexmo driver.
     *
     * @return \Illuminate\Notifications\Transports\NexmoSmsTransport
     */
    protected function createNexmoDriver()
    {
        return new Transports\NexmoSmsTransport(
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
     * @return \Illuminate\Notifications\Transports\SlackTransport
     */
    protected function createSlackDriver()
    {
        return $this->app->make(Transports\SlackTransport::class);
    }

    /**
     * Get the default transport driver name.
     *
     * @return string
     */
    public function getDefaultDriver()
    {
        return $this->app['config']['notifications.driver'];
    }

    /**
     * Set the default transport driver name.
     *
     * @param  string  $name
     * @return void
     */
    public function setDefaultDriver($name)
    {
        $this->app['config']['notifications.driver'] = $name;
    }
}
