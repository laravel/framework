<?php

namespace Illuminate\Tests\Integration\Auth;

use Illuminate\Auth\Events\PasswordResetLinkSent;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Tests\Integration\Auth\Fixtures\AuthenticationTestUser;
use Orchestra\Testbench\Attributes\WithConfig;
use Orchestra\Testbench\Attributes\WithMigration;
use Orchestra\Testbench\Factories\UserFactory;
use Orchestra\Testbench\TestCase;
use PHPUnit\Framework\Attributes\TestWith;

#[WithMigration]
class ForgotPasswordTest extends TestCase
{
    use RefreshDatabase;

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

    protected function defineRoutes($router)
    {
        $router->get('password/reset/{token}', function ($token) {
            return 'Reset password!';
        })->name('password.reset');

        $router->get('custom/password/reset/{token}', function ($token) {
            return 'Custom reset password!';
        })->name('custom.password.reset');
    }

    public function testItCanSendForgotPasswordEmail()
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

    #[WithConfig('app.url', 'https://app.example')]
    #[TestWith([[]])]
    #[TestWith([['.*']])]
    public function testPasswordResetAndVerificationLinksIgnoreForgedHost(array $trustedHosts)
    {
        Request::setTrustedHosts($trustedHosts);
        $this->beforeApplicationDestroyed(fn () => Request::setTrustedHosts([]));
        Notification::fake();
        $user = UserFactory::new()->create();

        $this->app['router']->get('email/verify/{id}/{hash}', fn () => '')->name('verification.verify');
        $this->app['router']->post('security-links', function () use ($user) {
            Password::broker()->sendResetLink(['email' => $user->email]);

            $resetUrl = null;
            Notification::assertSentTo($user, function (ResetPassword $notification) use ($user, &$resetUrl) {
                $resetUrl = $notification->toMail($user)->actionUrl;

                return true;
            });

            $notifiable = new class extends AuthenticationTestUser
            {
                use \Illuminate\Auth\MustVerifyEmail;
            };
            $notifiable->setRawAttributes(['id' => $user->getKey(), 'email' => $user->email]);

            return ['host' => request()->getHost(), 'reset' => $resetUrl, 'verify' => (new VerifyEmail)->toMail($notifiable)->actionUrl];
        });

        $response = $this->post('http://attacker.example/security-links');
        $response->assertOk();
        $response->assertJsonPath('host', 'attacker.example');

        $resetUrl = $response->json('reset');
        $verifyUrl = $response->json('verify');
        $this->assertStringStartsWith('https://app.example/password/reset/', $resetUrl);
        $this->assertTrue(Password::broker()->tokenExists($user, basename(parse_url($resetUrl, PHP_URL_PATH))));
        $this->assertStringContainsString('email='.rawurlencode($user->email), $resetUrl);
        $this->assertStringStartsWith('https://app.example/email/verify/'.$user->getKey().'/', $verifyUrl);
        $this->assertTrue($this->app['url']->hasValidSignature(Request::create($verifyUrl)));
        $this->assertFalse($this->app['url']->hasValidSignature(Request::create(str_replace('app.example', 'attacker.example', $verifyUrl))));
    }

    public function testItCanTriggerPasswordResetSentEvent()
    {
        Event::fake([PasswordResetLinkSent::class]);

        UserFactory::new()->create();

        $user = AuthenticationTestUser::first();

        Password::broker()->sendResetLink([
            'email' => $user->email,
        ]);

        Event::assertDispatched(PasswordResetLinkSent::class, function ($event) {
            $this->assertEquals(1, $event->user->id);

            return true;
        });
    }

    public function testItCanSendForgotPasswordEmailViaCreateUrlUsing()
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

    public function testItCanSendForgotPasswordEmailViaToMailUsing()
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
