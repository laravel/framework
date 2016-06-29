<?php

namespace Illuminate\Notifications\Channels;

use Illuminate\Support\Arr;
use Illuminate\Contracts\Mail\Mailer;
use Illuminate\Contracts\View\Factory as ViewFactory;

class MailChannel
{
    /**
     * The mailer implementation.
     *
     * @var \Illuminate\Contracts\Mail\Mailer
     */
    protected $mailer;

    /**
     * The view factory implementation.
     *
     * @var \Illuminate\Contracts\View\Factory
     */
    protected $views;

    /**
     * Create a new mail channel instance.
     *
     * @param  \Illuminate\Contracts\Mail\Mailer  $mailer
     * @param  \Illuminate\Contracts\View\Factory  $views
     * @return void
     */
    public function __construct(Mailer $mailer, ViewFactory $views)
    {
        $this->views = $views;
        $this->mailer = $mailer;
    }

    /**
     * Send the given notification.
     *
     * @param  \Illuminate\Notifications\Channels\Notification  $notification
     * @return void
     */
    public function send(Notification $notification)
    {
        $data = $this->prepareNotificationData($notification);

        $emails = $notification->notifiables->map(function ($n) {
            return $n->routeNotificationFor('mail');
        })->filter()->all();

        if (empty($emails)) {
            return;
        }

        $view = data_get($notification, 'options.view', 'notifications::email');

        $this->mailer->send('notifications::email', $data, function ($m) use ($notification, $emails) {
            count($notification->notifiables) === 1
                        ? $m->to($emails) : $m->bcc($emails);

            $m->subject($notification->subject);
        });
    }

    /**
     * Prepare the data from the given notification.
     *
     * @param  \Illuminate\Notifications\Channels\Notification  $notification
     * @return void
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
