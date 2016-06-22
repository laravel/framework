<?php

namespace Illuminate\Auth\Notifications;

use Illuminate\Notifications\Notification;

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
     * Get the notification message.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function message($notifiable)
    {
        return $this->line('You are receiving this email because we received a password reset request for your account. Click the button below to reset your password:')
                    ->action('Reset Password', url('password/reset', $this->token).'?email='.urlencode($notifiable->email))
                    ->line('If you did not request a password reset, no further action is required.');
    }
}
