<?php

namespace Illuminate\Tests\Integration\Notifications;

use Illuminate\Mail\Mailable;
use Illuminate\Support\Carbon;
use Orchestra\Testbench\TestCase;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Notifications\Notification;
use Illuminate\Foundation\Events\LocaleUpdated;
use Illuminate\Notifications\Channels\MailChannel;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Contracts\Translation\HasLocalePreference;
use Illuminate\Support\Facades\Notification as NotificationFacade;

/**
 * @group integration
 */
class SendingNotificationsWithLocaleTest extends TestCase
{
    public $mailer;

    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('app.debug', 'true');

        $app['config']->set('mail.driver', 'array');

        $app['config']->set('app.locale', 'en');

        $app['config']->set('database.default', 'testbench');

        $app['config']->set('database.connections.testbench', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        View::addLocation(__DIR__.'/Fixtures');

        app('translator')->setLoaded([
            '*' => [
                '*' => [
                    'en' => ['hi' => 'hello'],
                    'es' => ['hi' => 'hola'],
                    'fr' => ['hi' => 'bonjour'],
                ],
            ],
        ]);
    }

    public function setUp()
    {
        parent::setUp();

        Schema::create('users', function ($table) {
            $table->increments('id');
            $table->string('email');
            $table->string('name')->nullable();
        });
    }

    public function test_mail_is_sent_with_default_locale()
    {
        $user = NotifiableLocalizedUser::forceCreate([
            'email' => 'taylor@laravel.com',
            'name' => 'Taylor Otwell',
        ]);

        NotificationFacade::send($user, new GreetingMailNotification);

        $this->assertContains('hello',
            app('swift.transport')->messages()[0]->getBody()
        );
    }

    public function test_mail_is_sent_with_facade_selected_locale()
    {
        $user = NotifiableLocalizedUser::forceCreate([
            'email' => 'taylor@laravel.com',
            'name' => 'Taylor Otwell',
        ]);

        NotificationFacade::locale('fr')->send($user, new GreetingMailNotification);

        $this->assertContains('bonjour',
            app('swift.transport')->messages()[0]->getBody()
        );
    }

    public function test_mail_is_sent_with_notification_selected_locale()
    {
        $users = [
            NotifiableLocalizedUser::forceCreate([
                'email' => 'taylor@laravel.com',
                'name' => 'Taylor Otwell',
            ]),
            NotifiableLocalizedUser::forceCreate([
                'email' => 'mohamed@laravel.com',
                'name' => 'Mohamed Said',
            ]),
        ];

        NotificationFacade::send($users, (new GreetingMailNotification())->locale('fr'));

        $this->assertContains('bonjour',
            app('swift.transport')->messages()[0]->getBody()
        );

        $this->assertContains('bonjour',
            app('swift.transport')->messages()[1]->getBody()
        );
    }

    public function test_mailable_is_sent_with_selected_locale()
    {
        $user = NotifiableLocalizedUser::forceCreate([
            'email' => 'taylor@laravel.com',
            'name' => 'Taylor Otwell',
        ]);

        NotificationFacade::locale('fr')->send($user, new GreetingMailNotificationWithMailable);

        $this->assertContains('bonjour',
            app('swift.transport')->messages()[0]->getBody()
        );
    }

    public function test_mail_is_sent_with_locale_updated_listeners_called()
    {
        Carbon::setTestNow(Carbon::parse('2018-07-25'));

        Event::listen(LocaleUpdated::class, function ($event) {
            Carbon::setLocale($event->locale);
        });

        $user = NotifiableLocalizedUser::forceCreate([
            'email' => 'taylor@laravel.com',
            'name' => 'Taylor Otwell',
        ]);

        $user->notify((new GreetingMailNotification())->locale('fr'));

        $this->assertContains('bonjour',
            app('swift.transport')->messages()[0]->getBody()
        );

        $this->assertRegExp('/dans (1|un) jour/',
            app('swift.transport')->messages()[0]->getBody()
        );

        $this->assertTrue($this->app->isLocale('en'));

        $this->assertSame('en', Carbon::getLocale());
    }

    public function test_locale_is_sent_with_notifiable_preferred_locale()
    {
        $recipient = new NotifiableEmailLocalePreferredUser([
            'email' => 'test@mail.com',
            'email_locale' => 'fr',
        ]);

        $recipient->notify(new GreetingMailNotification());

        $this->assertContains('bonjour',
            app('swift.transport')->messages()[0]->getBody()
        );
    }

    public function test_locale_is_sent_with_notifiable_preferred_locale_for_multiple_recipients()
    {
        $recipients = [
            new NotifiableEmailLocalePreferredUser([
                'email' => 'test@mail.com',
                'email_locale' => 'fr',
            ]),
            new NotifiableEmailLocalePreferredUser([
                'email' => 'test.2@mail.com',
                'email_locale' => 'es',
            ]),
            NotifiableLocalizedUser::forceCreate([
                'email' => 'test.3@mail.com',
            ]),
        ];

        NotificationFacade::send(
            $recipients, new GreetingMailNotification()
        );

        $this->assertContains('bonjour',
            app('swift.transport')->messages()[0]->getBody()
        );
        $this->assertContains('hola',
            app('swift.transport')->messages()[1]->getBody()
        );
        $this->assertContains('hi',
            app('swift.transport')->messages()[2]->getBody()
        );
    }

    public function test_locale_is_sent_with_notification_selected_locale_overriding_notifiable_preferred_locale()
    {
        $recipient = new NotifiableEmailLocalePreferredUser([
            'email' => 'test@mail.com',
            'email_locale' => 'es',
        ]);

        $recipient->notify(
            (new GreetingMailNotification())->locale('fr')
        );

        $this->assertContains('bonjour',
            app('swift.transport')->messages()[0]->getBody()
        );
    }

    public function test_locale_is_sent_with_facade_selected_locale_overriding_notifiable_preferred_locale()
    {
        $recipient = new NotifiableEmailLocalePreferredUser([
            'email' => 'test@mail.com',
            'email_locale' => 'es',
        ]);

        NotificationFacade::locale('fr')->send(
            $recipient, new GreetingMailNotification()
        );

        $this->assertContains('bonjour',
            app('swift.transport')->messages()[0]->getBody()
        );
    }
}

class NotifiableLocalizedUser extends Model
{
    use Notifiable;

    public $table = 'users';
    public $timestamps = false;
}

class NotifiableEmailLocalePreferredUser extends Model implements HasLocalePreference
{
    use Notifiable;

    protected $fillable = [
        'email',
        'email_locale',
    ];

    public function preferredLocale()
    {
        return $this->email_locale;
    }
}

class GreetingMailNotification extends Notification
{
    public function via($notifiable)
    {
        return [MailChannel::class];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->greeting(__('hi'))
            ->line(Carbon::tomorrow()->diffForHumans());
    }
}

class GreetingMailNotificationWithMailable extends Notification
{
    public function via($notifiable)
    {
        return [MailChannel::class];
    }

    public function toMail($notifiable)
    {
        return new GreetingMailable;
    }
}

class GreetingMailable extends Mailable
{
    public function build()
    {
        return $this->view('greeting');
    }
}
