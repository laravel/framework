<?php

namespace Illuminate\Tests\Integration\Auth;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Tests\Integration\Auth\Fixtures\AuthenticationTestUser;
use Orchestra\Testbench\Factories\UserFactory;
use Orchestra\Testbench\TestCase;

class ForgotPasswordTest extends TestCase
{
    protected function tearDown(): void
    {
        ResetPassword::$createUrlCallback = null;
        ResetPassword::$toMailCallback = null;

        parent::tearDown();
    }

    protected function defineEnvironment($app)
    {
        $app['config']->set('app.key', Str::random(32));
        $app['config']->set('auth.providers.users.model', AuthenticationTestUser::class);
    }

    protected function defineDatabaseMigrations()
    {
        $this->loadLaravelMigrations();
    }

    protected function defineRoutes($router)
    {
        $router->get('password/reset/{token}', function ($token) {
            return 'Reset password!';
        })->name('password.reset');

        $router->get('custom/password/reset/{token}', function ($token) {
            return 'Custom reset password!';
        })->name('custom.password.reset');
    }

    /** @test */
    public function it_can_send_forgot_password_email()
    {
        Notification::fake();

        UserFactory::new()->create();

        $user = AuthenticationTestUser::first();

        Password::broker()->sendResetLink([
            'email' => $user->email,
        ]);

        Notification::assertSentTo(
            $user,
            function (ResetPassword $notification, $channels) use ($user) {
                $message = $notification->toMail($user);

                return ! is_null($notification->token)
                    && $message->actionUrl === route('password.reset', ['token' => $notification->token, 'email' => $user->email]);
            }
        );
    }

    /** @test */
    public function it_can_send_forgot_password_email_via_create_url_using()
    {
        Notification::fake();

        ResetPassword::createUrlUsing(function ($user, string $token) {
            return route('custom.password.reset', $token);
        });

        UserFactory::new()->create();

        $user = AuthenticationTestUser::first();

        Password::broker()->sendResetLink([
            'email' => $user->email,
        ]);

        Notification::assertSentTo(
            $user,
            function (ResetPassword $notification, $channels) use ($user) {
                $message = $notification->toMail($user);

                return ! is_null($notification->token)
                    && $message->actionUrl === route('custom.password.reset', ['token' => $notification->token]);
            }
        );
    }

    /** @test */
    public function it_can_send_forgot_password_email_via_to_mail_using()
    {
        Notification::fake();

        ResetPassword::toMailUsing(function ($notifiable, $token) {
            return (new MailMessage)
                ->subject(__('Reset Password Notification'))
                ->line(__('You are receiving this email because we received a password reset request for your account.'))
                ->action(__('Reset Password'), route('custom.password.reset', $token))
                ->line(__('If you did not request a password reset, no further action is required.'));
        });

        UserFactory::new()->create();

        $user = AuthenticationTestUser::first();

        Password::broker()->sendResetLink([
            'email' => $user->email,
        ]);

        Notification::assertSentTo(
            $user,
            function (ResetPassword $notification, $channels) use ($user) {
                $message = $notification->toMail($user);

                return ! is_null($notification->token)
                    && $message->actionUrl === route('custom.password.reset', ['token' => $notification->token]);
            }
        );
    }
}
