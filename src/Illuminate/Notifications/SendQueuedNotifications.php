<?php

namespace Illuminate\Notifications;

use Illuminate\Contracts\Queue\ShouldQueue;

class SendQueuedNotifications implements ShouldQueue
{
    /**
     * The notifications to be sent.
     *
     * @var array
     */
    protected $notifications;

    /**
     * Create a new job instance.
     *
     * @param  array[\Illuminate\Notifications\Transports\Notification]  $notifications
     * @return void
     */
    public function __construct(array $notifications)
    {
        $this->notifications = $notifications;
    }

    /**
     * Send the notifications.
     *
     * @return void
     */
    public function handle()
    {
        $manager = app(TransportManager::class);

        foreach ($this->notifications as $notification) {
            $manager->send($notification->application(
                config('app.name'), config('app.logo')
            ));
        }
    }
}
