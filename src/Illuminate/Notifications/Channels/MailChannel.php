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
        $data = $this->prepareNotificationData($notification);

        $emails = $notifiables->map(function ($n) {
            return $n->routeNotificationFor('mail');
        })->filter()->all();

        if (empty($emails)) {
            return;
        }

        $view = data_get($notification, 'options.view', 'notifications::email');

        $this->mailer->send($view, $data, function ($m) use ($notifiables, $notification, $emails) {
            count($notifiables) === 1
                        ? $m->to($emails) : $m->bcc($emails);

            $m->subject($notification->subject ?: Str::title(
                Str::snake(class_basename($notification), ' ')
            ));
        });
    }

    /**
     * Prepare the data from the given notification.
     *
     * @param  \Illuminate\Notifications\Notification  $notification
     * @return array
     */
    protected function prepareNotificationData($notification)
    {
        $data = $notification->toArray();

        return Arr::set($data, 'actionColor', $this->actionColorForLevel($data['level']));
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
