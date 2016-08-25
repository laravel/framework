<?php

namespace Illuminate\Auth\Notifications;

use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class ResetPassword extends Notification
{
    /**
     * The password reset token.
     *
     * @var string
     */
    public $token;

    /**
     * Create a notification instance.
     *
     * @param  string  $token
     * @return void
     */
    public function __construct($token)
    {
        $this->token = $token;
    }

    /**
     * Get the notification's channels.
     *
     * @param  mixed  $notifiable
     * @return array|string
     */
    public function via($notifiable)
    {
        return ['mail'];
    }

    /**
     * Build the mail representation of the notification.
     *
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail()
    {
        return (new MailMessage)
            ->line([
                $this->getTranslation(
                    'passwords.reset_email_line1',
                    'You are receiving this email because we received a password reset request for your account.'
                ),
                $this->getTranslation(
                    'passwords.reset_email_line2',
                    'Click the button below to reset your password:'
                ),
            ])
            ->action(
                $this->getTranslation('passwords.reset_email_action', 'Reset Password'),
                url('password/reset', $this->token)
            )
            ->line(
                $this->getTranslation(
                    'passwords.reset_email_footer',
                    'If you did not request a password reset, no further action is required.'
                )
            );
    }

    /**
     * Get the value of a language line.
     *
     * @param  string $key
     * @param  string $default
     * @return string
     */
    public function getTranslation($key, $default = '')
    {
        return app('translator')->has($key)
            ? trans($key) : $default;
    }
}
