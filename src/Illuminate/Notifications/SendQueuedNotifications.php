<?php

namespace Illuminate\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendQueuedNotifications implements ShouldQueue
{
    use Queueable;

    /**
     * The notifications to be sent.
     *
     * @var array
     */
    protected $notifications;

    /**
     * Create a new job instance.
     *
     * @param  array[\Illuminate\Notifications\Channels\Notification]  $notifications
     * @return void
     */
    public function __construct(array $notifications)
    {
        $this->notifications = $notifications;
    }

    /**
     * Send the notifications.
     *
     * @param  \Illuminate\Notifications\ChannelManager  $manager
     * @return void
     */
    public function handle(ChannelManager $manager)
    {
        foreach ($this->notifications as $notification) {
            $manager->send($notification->application(
                config('app.name'), config('app.logo')
            ));
        }
    }
}
