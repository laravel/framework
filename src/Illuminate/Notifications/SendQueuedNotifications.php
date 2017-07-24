<?php

namespace Illuminate\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendQueuedNotifications implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * The notifiable entities that should receive the notification.
     *
     * @var \Illuminate\Support\Collection
     */
    public $notifiables;

    /**
     * The notification to be sent.
     *
     * @var \Illuminate\Notifications\Notification
     */
    public $notification;

    /**
     * All of the channels to send the notification too.
     *
     * @var array
     */
    public $channels;

    /**
     * Create a new job instance.
     *
     * @param  \Illuminate\Support\Collection  $notifiables
     * @param  \Illuminate\Notifications\Notification  $notification
     * @param  array  $channels
     * @return void
     */
    public function __construct($notifiables, $notification, array $channels = null)
    {
        $this->channels = $channels;
        $this->notifiables = $notifiables;
        $this->notification = $notification;
    }

    /**
     * Send the notifications.
     *
     * @param  \Illuminate\Notifications\ChannelManager  $manager
     * @return void
     */
    public function handle(ChannelManager $manager)
    {
        $manager->sendNow($this->notifiables, $this->notification, $this->channels);
    }

    /**
     * Get the display name for the queued job.
     *
     * @return string
     */
    public function displayName()
    {
        return get_class($this->notification);
    }
}
