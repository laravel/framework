<?php

namespace Illuminate\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class SendQueuedNotifications implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

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
     * All of the channels to send the notification to.
     *
     * @var array
     */
    public $channels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout;

    /**
     * Create a new job instance.
     *
     * @param  mixed  $notifiables
     * @param  \Illuminate\Notifications\Notification  $notification
     * @param  array|null  $channels
     * @return void
     */
    public function __construct($notifiables, $notification, array $channels = null)
    {
        $this->channels = $channels;
        $this->notification = $notification;
        $this->notifiables = $this->wrapNotifiables($notifiables);
        $this->tries = property_exists($notification, 'tries') ? $notification->tries : null;
        $this->timeout = property_exists($notification, 'timeout') ? $notification->timeout : null;
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

    /**
     * Call the failed method on the notification instance.
     *
     * @param  \Throwable  $e
     * @return void
     */
    public function failed($e)
    {
        if (method_exists($this->notification, 'failed')) {
            $this->notification->failed($e);
        }
    }

    /**
     * Get the retry delay for the notification.
     *
     * @return mixed
     */
    public function retryAfter()
    {
        if (! method_exists($this->notification, 'retryAfter') && ! isset($this->notification->retryAfter)) {
            return;
        }

        return $this->notification->retryAfter ?? $this->notification->retryAfter();
    }

    /**
     * Get the expiration for the notification.
     *
     * @return mixed
     */
    public function retryUntil()
    {
        if (! method_exists($this->notification, 'retryUntil') && ! isset($this->notification->timeoutAt)) {
            return;
        }

        return $this->notification->timeoutAt ?? $this->notification->retryUntil();
    }

    /**
     * Prepare the instance for cloning.
     *
     * @return void
     */
    public function __clone()
    {
        $this->notifiables = clone $this->notifiables;
        $this->notification = clone $this->notification;
    }

    /**
     * Wrap the notifiable(s) in a collection.
     *
     * @param mixed $notifiables
     * @return \Illuminate\Support\Collection
     */
    protected function wrapNotifiables($notifiables)
    {
        if ($notifiables instanceof Collection) {
            // If the notifiable(s) are already wrapped, pass them as is.
            // This prevents any custom queueable collections from being re-wrapped
            return $notifiables;
        }

        if ($notifiables instanceof Model) {
            // In the case of a model we want to wrap it in an eloquent collection
            // This way the job can take advantage of model serialization
            return EloquentCollection::wrap($notifiables);
        }

        return Collection::wrap($notifiables);
    }
}
