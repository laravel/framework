<?php

namespace Illuminate\Notifications\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Arr;

class BroadcastNotificationCreated implements ShouldBroadcast
{
    use Queueable, SerializesModels;

    /**
     * The notifiable entity who received the notification.
     *
     * @var mixed
     */
    public $notifiable;

    /**
     * The notification instance.
     *
     * @var \Illuminate\Notifications\Notification
     */
    public $notification;

    /**
     * The notification data.
     *
     * @var array
     */
    public $data = [];

    /**
     * Create a new event instance.
     *
     * @param  mixed  $notifiable
     * @param  \Illuminate\Notifications\Notification  $notification
     * @param  array  $data
     * @return void
     */
    public function __construct($notifiable, $notification, $data)
    {
        $this->data = $data;
        $this->notifiable = $notifiable;
        $this->notification = $notification;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array
     */
    public function broadcastOn()
    {
        if ($this->notifiable instanceof AnonymousNotifiable &&
            $this->notifiable->routeNotificationFor('broadcast')) {
            $channels = Arr::wrap($this->notifiable->routeNotificationFor('broadcast'));
        } else {
            $channels = $this->notification->broadcastOn();
        }

        if (! empty($channels)) {
            return $channels;
        }

        if (is_string($channels = $this->channelName())) {
            return [new PrivateChannel($channels)];
        }

        return collect($channels)->map(function ($channel) {
            return new PrivateChannel($channel);
        })->all();
    }

    /**
     * Get the broadcast channel name for the event.
     *
     * @return array|string
     */
    protected function channelName()
    {
        if (method_exists($this->notifiable, 'receivesBroadcastNotificationsOn')) {
            return $this->notifiable->receivesBroadcastNotificationsOn($this->notification);
        }

        $class = str_replace('\\', '.', get_class($this->notifiable));

        return $class.'.'.$this->notifiable->getKey();
    }

    /**
     * Get the data that should be sent with the broadcasted event.
     *
     * @return array
     */
    public function broadcastWith()
    {
        if (method_exists($this->notification, 'broadcastWith')) {
            return $this->notification->broadcastWith();
        }

        return array_merge($this->data, [
            'id' => $this->notification->id,
            'type' => $this->broadcastType(),
        ]);
    }

    /**
     * Get the type of the notification being broadcast.
     *
     * @return string
     */
    public function broadcastType()
    {
        return method_exists($this->notification, 'broadcastType')
                    ? $this->notification->broadcastType()
                    : get_class($this->notification);
    }

    /**
     * Get the event name of the notification being broadcast.
     *
     * @return string
     */
    public function broadcastAs()
    {
        return method_exists($this->notification, 'broadcastAs')
        ? $this->notification->broadcastAs()
        : get_class($this->notification);
    }
}
