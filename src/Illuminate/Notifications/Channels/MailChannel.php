<?php

namespace Illuminate\Notifications\Channels;

use Closure;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Contracts\Mail\Mailer;
use Illuminate\Contracts\Mail\Mailable;
use Illuminate\Notifications\Notification;

class MailChannel
{
    /**
     * The mailer implementation.
     *
     * @var \Illuminate\Contracts\Mail\Mailer
     */
    protected $mailer;

    /**
     * The Markdown resolver callback.
     *
     * @var \Closure
     */
    protected $markdownResolver;

    /**
     * Create a new mail channel instance.
     *
     * @param  \Illuminate\Contracts\Mail\Mailer  $mailer
     * @return void
     */
    public function __construct(Mailer $mailer)
    {
        $this->mailer = $mailer;
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
        if (! $notifiable->routeNotificationFor('mail')) {
            return;
        }

        $message = $notification->toMail($notifiable);

        if ($message instanceof Mailable) {
            return $message->send($this->mailer);
        }

        $this->mailer->send($this->buildView($message), $message->data(), function ($mailMessage) use ($notifiable, $notification, $message) {
            $this->buildMessage($mailMessage, $notifiable, $notification, $message);
        });
    }

    /**
     * Build the notification's view.
     *
     * @param  \Illuminate\Notifications\Messages\MailMessage  $message
     * @return void
     */
    protected function buildView($message)
    {
        if ($message->view) {
            return $message->view;
        }

        $markdown = call_user_func($this->markdownResolver);

        return [
            'html' => $markdown->render($message->markdown, $message->data()),
            'text' => $markdown->renderText($message->markdown, $message->data()),
        ];
    }

    /**
     * Build the mail message.
     *
     * @param  \Illuminate\Mail\Message  $mailMessage
     * @param  mixed  $notifiable
     * @param  \Illuminate\Notifications\Notification  $notification
     * @param  \Illuminate\Notifications\Messages\MailMessage  $message
     * @return void
     */
    protected function buildMessage($mailMessage, $notifiable, $notification, $message)
    {
        $this->addressMessage($mailMessage, $notifiable, $message);

        $mailMessage->subject($message->subject ?: Str::title(
            Str::snake(class_basename($notification), ' ')
        ));

        $this->addAttachments($mailMessage, $message);

        if (! is_null($message->priority)) {
            $mailMessage->setPriority($message->priority);
        }
    }

    /**
     * Address the mail message.
     *
     * @param  \Illuminate\Mail\Message  $mailMessage
     * @param  mixed  $notifiable
     * @param  \Illuminate\Notifications\Messages\MailMessage  $message
     * @return void
     */
    protected function addressMessage($mailMessage, $notifiable, $message)
    {
        $this->addSender($mailMessage, $message);

        $mailMessage->to($this->getRecipients($notifiable, $message));

        if ($message->cc) {
            $mailMessage->cc($message->cc[0], Arr::get($message->cc, 1));
        }

        if ($message->bcc) {
            $mailMessage->bcc($message->bcc[0], Arr::get($message->bcc, 1));
        }
    }

    /**
     * Add the "from" and "reply to" addresses to the message.
     *
     * @param  \Illuminate\Mail\Message  $mailMessage
     * @param  \Illuminate\Notifications\Messages\MailMessage  $message
     * @return void
     */
    protected function addSender($mailMessage, $message)
    {
        if (! empty($message->from)) {
            $mailMessage->from($message->from[0], Arr::get($message->from, 1));
        }

        if (! empty($message->replyTo)) {
            $mailMessage->replyTo($message->replyTo[0], Arr::get($message->replyTo, 1));
        }
    }

    /**
     * Get the recipients of the given message.
     *
     * @param  mixed  $notifiable
     * @param  \Illuminate\Notifications\Messages\MailMessage  $message
     * @return mixed
     */
    protected function getRecipients($notifiable, $message)
    {
        if (is_string($recipients = $notifiable->routeNotificationFor('mail'))) {
            $recipients = [$recipients];
        }

        return collect($recipients)->map(function ($recipient) {
            return is_string($recipient) ? $recipient : $recipient->email;
        })->all();
    }

    /**
     * Add the attachments to the message.
     *
     * @param  \Illuminate\Mail\Message  $mailMessage
     * @param  \Illuminate\Notifications\Messages\MailMessage  $message
     * @return void
     */
    protected function addAttachments($mailMessage, $message)
    {
        foreach ($message->attachments as $attachment) {
            $mailMessage->attach($attachment['file'], $attachment['options']);
        }

        foreach ($message->rawAttachments as $attachment) {
            $mailMessage->attachData($attachment['data'], $attachment['name'], $attachment['options']);
        }
    }

    /**
     * Set the Markdown resolver callback.
     *
     * @param  \Closure  $callback
     * @return $this
     */
    public function setMarkdownResolver(Closure $callback)
    {
        $this->markdownResolver = $callback;

        return $this;
    }
}
