<?php

namespace Illuminate\Notifications\Channels;

use Illuminate\Contracts\Mail\Factory as MailFactory;
use Illuminate\Contracts\Mail\Mailable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Markdown;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

class MailChannel
{
    /**
     * The mailer implementation.
     *
     * @var \Illuminate\Contracts\Mail\Factory
     */
    protected $mailer;

    /**
     * The markdown implementation.
     *
     * @var \Illuminate\Mail\Markdown
     */
    protected $markdown;

    /**
     * Create a new mail channel instance.
     *
     * @param  \Illuminate\Contracts\Mail\Factory  $mailer
     * @param  \Illuminate\Mail\Markdown  $markdown
     * @return void
     */
    public function __construct(MailFactory $mailer, Markdown $markdown)
    {
        $this->mailer = $mailer;
        $this->markdown = $markdown;
    }

    /**
     * Send the given notification.
     *
     * @param  mixed  $notifiable
     * @param  \Illuminate\Notifications\Notification  $notification
     * @return void
     */
    public function send($notifiable, Notification $notification)
    {
        $message = $notification->toMail($notifiable);

        if (! $notifiable->routeNotificationFor('mail', $notification) &&
            ! $message instanceof Mailable) {
            return;
        }

        if ($message instanceof MailMessage) {
            return $this->buildMessage($message, $notifiable, $notification)
                ->send($this->mailer);
        }

        $message->send($this->mailer);
    }

    /**
     * Get additional meta-data to pass along with the view data.
     *
     * @param  \Illuminate\Notifications\Notification  $notification
     * @return array
     */
    protected function additionalMessageData($notification)
    {
        return [
            '__laravel_notification_id' => $notification->id,
            '__laravel_notification' => get_class($notification),
            '__laravel_notification_queued' => in_array(
                ShouldQueue::class,
                class_implements($notification)
            ),
        ];
    }

    /**
     * Build the mail message.
     *
     * @param  \Illuminate\Mail\Mailable  $message
     * @param  mixed  $notifiable
     * @param  \Illuminate\Notifications\Notification  $notification
     * @return \Illuminate\Mail\Mailable
     */
    protected function buildMessage($message, $notifiable, $notification)
    {
        $message->with(array_merge(
            $message->data(),
            $this->additionalMessageData($notification)
        ));

        $message->to(
            $this->getRecipients($notifiable, $notification, $message)
        );

        $message->subject($message->subject ?: Str::title(
            Str::snake(class_basename($notification), ' ')
        ));

        return $message;
    }

    /**
     * Get the recipients of the given message.
     *
     * @param  mixed  $notifiable
     * @param  \Illuminate\Notifications\Notification  $notification
     * @return mixed
     */
    protected function getRecipients($notifiable, $notification)
    {
        if (is_string($recipients = $notifiable->routeNotificationFor('mail', $notification))) {
            $recipients = [$recipients];
        }

        return collect($recipients)->map(function ($recipient, $email) {
            return is_numeric($email)
                    ? ['email' => $recipient, 'name' => null]
                    : ['email' => $email, 'name' => $recipient];
        })->values()->all();
    }
}
