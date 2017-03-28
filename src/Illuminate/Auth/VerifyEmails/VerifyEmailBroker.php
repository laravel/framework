<?php

namespace Illuminate\Auth\VerifyEmails;

use Closure;
use Illuminate\Contracts\Mail\Mailer as MailerContract;
use Illuminate\Contracts\Auth\VerifyEmailBroker as VerifyEmailBrokerContract;
use Illuminate\Contracts\Auth\CanVerifyEmail as CanVerifyEmailContract;

class VerifyEmailBroker implements VerifyEmailBrokerContract
{
    /**
     * The token repository.
     *
     * @var \Illuminate\Auth\VerifyEmails\TokenRepositoryInterface
     */
    protected $tokens;

    /**
     * The mailer instance.
     *
     * @var \Illuminate\Contracts\Mail\Mailer
     */
    protected $mailer;

    /**
     * The view of the verify email link e-mail.
     *
     * @var string
     */
    protected $emailView;

    /**
     * Create a new verify email broker instance.
     *
     * @param  \Illuminate\Auth\Passwords\TokenRepositoryInterface  $tokens
     * @param  \Illuminate\Contracts\Mail\Mailer  $mailer
     * @param  string  $emailView
     * @return void
     */
    public function __construct(TokenRepositoryInterface $tokens, MailerContract $mailer, $emailView)
    {
        $this->mailer = $mailer;
        $this->tokens = $tokens;
        $this->emailView = $emailView;
    }

    /**
     * Send an email verification link to a user.
     *
     * @param  \Illuminate\Contracts\Auth\CanVerifyEmail  $user
     * @param  \Closure|null  $callback
     * @return string
     */
    public function sendVerificationLink(CanVerifyEmailContract $user, Closure $callback = null)
    {
        // Once we have the verify token, we are ready to send the message out to this
        // user with a link to verify their email. We will then redirect back to
        // the current URI having nothing set in the session to indicate errors.
        $token = $this->tokens->create($user);

        $this->emailVerificationLink($user, $token, $callback);

        return VerifyEmailBrokerContract::VERIFY_LINK_SENT;
    }

    /**
     * Send the email verification link via e-mail.
     *
     * @param  \Illuminate\Contracts\Auth\CanVerifyEmail  $user
     * @param  string  $token
     * @param  \Closure|null  $callback
     * @return int
     */
    public function emailVerificationLink(CanVerifyEmailContract $user, $token, Closure $callback = null)
    {
        // We will use the view that was given to the broker to display the
        // verification e-mail. We'll pass a "token" variable into the views
        // so that it may be displayed for an user to click to verify.
        $view = $this->emailView;

        return $this->mailer->send($view, compact('token', 'user'), function ($m) use ($user, $token, $callback) {
            $m->to($user->getEmailToVerify());

            if (! is_null($callback)) {
                call_user_func($callback, $m, $user, $token);
            }
        });
    }

    /**
     * Verify the email address for the given token.
     *
     * @param  \Illuminate\Contracts\Auth\CanVerifyEmail  $user
     * @param  string  $token
     * @return mixed
     */
    public function verify(CanVerifyEmailContract $user, $token)
    {
        // If the responses from the validate method is not a user instance, we will
        // assume that it is a redirect and simply return it from this method and
        // the user is properly redirected having an error message on the post.
        $user = $this->validateVerification($user, $token);

        if (! $user instanceof CanVerifyEmailContract) {
            return $user;
        }

        $this->tokens->delete($token);

        $user->setVerified(true);

        $user->save();

        return VerifyEmailBrokerContract::EMAIL_VERIFIED;
    }

    /**
     * Validate a verification for the given credentials.
     *
     * @param  \Illuminate\Contracts\Auth\CanVerifyEmail  $user
     * @param  string  $token
     * @return \Illuminate\Contracts\Auth\CanVerifyEmail
     */
    protected function validateVerification(CanVerifyEmailContract $user, $token)
    {
        if (! $this->tokens->exists($user, $token)) {
            return VerifyEmailBrokerContract::INVALID_TOKEN;
        }

        return $user;
    }

    /**
     * Get the email verification token repository implementation.
     *
     * @return \Illuminate\Auth\Passwords\TokenRepositoryInterface
     */
    protected function getRepository()
    {
        return $this->tokens;
    }
}
