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
}

class NotifiableLocalizedUser extends Model
{
    use Notifiable;

    public $table = 'users';
    public $timestamps = false;
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
