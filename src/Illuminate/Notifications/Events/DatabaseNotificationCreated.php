<?php

namespace Illuminate\Notifications\Events;

use Illuminate\Queue\SerializesModels;
use Illuminate\Notifications\Notification;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class DatabaseNotificationCreated implements ShouldBroadcast
{
    use SerializesModels;

    /**
     * The notifiable entity who received the notification.
     *
     * @var mixed
     */
    public $notifiable;

    /**
     * The notification message instance.
     *
     * @var \Illuminate\Notifications\Message
     */
    public $message;

    /**
     * The database notification instance.
     *
     * @var \Illuminate\Notifications\DatabaseNotification
     */
    public $databaseNotification;

    /**
     * Create a new event instance.
     *
     * @param  mixed  $notifiable
     * @param  \Illuminate\Notifications\Message  $message
     * @param  \Illuminate\Notifications\DatabaseNotification  $databaseNotification
     * @return void
     */
    public function __construct($notifiable, $message, $databaseNotification)
    {
        $this->notifiable = $notifiable;
        $this->message = $message;
        $this->databaseNotification = $databaseNotification;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array
     */
    public function broadcastOn()
    {
        return [new PrivateChannel($this->channelName())];
    }

    /**
     * Get the data that should be sent with the broadcasted event.
     *
     * @return array
     */
    public function broadcastWith()
    {
        return ['notification' => $this->databaseNotification];
    }

    /**
     * Get the broadcast channel name for the event.
     *
     * @return string
     */
    protected function channelName()
    {
        $class = str_replace('\\', '.', get_class($this->notifiable));

        return $class.'.'.$this->notifiable->getKey();
    }
}
