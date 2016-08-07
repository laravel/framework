<?php

namespace Illuminate\Notifications\Channels;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Contracts\Mail\Mailer;
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
     * @param  \Illuminate\Support\Collection  $notifiables
     * @param  \Illuminate\Notifications\Notification  $notification
     * @return void
     */
    public function send($notifiables, Notification $notification)
    {
        $view = data_get($notification, 'options.view', 'notifications::email');

        foreach ($notifiables as $notifiable) {
            if (! $notifiable->routeNotificationFor('mail')) {
                continue;
            }

            $data = $notification->toArray($notifiable);

            Arr::set($data, 'actionColor', $this->actionColorForLevel($data['level']));

            $this->mailer->send($view, $data, function ($m) use ($notifiable, $notification) {
                $m->to($notifiable->routeNotificationFor('mail'));

                $m->subject($notification->message($notifiable)->subject ?: Str::title(
                    Str::snake(class_basename($notification), ' ')
                ));
            });
        }
    }

    /**
     * Get the action color for the given notification "level".
     *
     * @param  string  $level
     * @return string
     */
    protected function actionColorForLevel($level)
    {
        switch ($level) {
            case 'success':
                return 'green';
            case 'error':
                return 'red';
            default:
                return 'blue';
        }
    }
}
