<?php

namespace Illuminate\Tests\Integration\Mail;

use Illuminate\Contracts\Translation\HasLocalePreference;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\LocaleUpdated;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Illuminate\Testing\Assert;
use Orchestra\Testbench\TestCase;

class SendingMailWithLocaleTest extends TestCase
{
    protected function defineEnvironment($app)
    {
        $app['config']->set('mail.driver', 'array');

        $app['config']->set('app.locale', 'en');

        $app['view']->addLocation(__DIR__.'/Fixtures');

        $app['translator']->setLoaded([
            '*' => [
                '*' => [
                    'en' => ['nom' => 'name'],
                    'ar' => ['nom' => 'esm'],
                    'es' => ['nom' => 'nombre'],
                ],
            ],
        ]);
    }

    public function testMailIsSentWithDefaultLocale()
    {
        Mail::to('test@mail.com')->send(new TestMail);

        $this->assertStringContainsString('name',
            app('mailer')->getSymfonyTransport()->messages()[0]->toString()
        );
    }

    public function testMailIsSentWithSelectedLocale()
    {
        Mail::to('test@mail.com')->locale('ar')->send(new TestMail);

        $this->assertStringContainsString('esm',
            app('mailer')->getSymfonyTransport()->messages()[0]->toString()
        );
    }

    public function testMailIsSentWithLocaleFromMailable()
    {
        $mailable = new TestMail;
        $mailable->locale('ar');

        Mail::to('test@mail.com')->send($mailable);

        $this->assertStringContainsString('esm',
            app('mailer')->getSymfonyTransport()->messages()[0]->toString()
        );
    }

    public function testMailIsSentWithLocaleUpdatedListenersCalled()
    {
        Carbon::setTestNow('2018-04-01');

        Event::listen(LocaleUpdated::class, function ($event) {
            Carbon::setLocale($event->locale);
        });

        Mail::to('test@mail.com')->locale('es')->send(new TimestampTestMail);

        Assert::assertMatchesRegularExpression('/nombre (en|dentro de) (un|1) d=C3=ADa/',
            app('mailer')->getSymfonyTransport()->messages()[0]->toString()
        );

        $this->assertSame('en', Carbon::getLocale());

        Carbon::setTestNow(null);
    }

    public function testLocaleIsSentWithModelPreferredLocale()
    {
        $recipient = new TestEmailLocaleUser([
            'email' => 'test@mail.com',
            'email_locale' => 'ar',
        ]);

        Mail::to($recipient)->send(new TestMail);

        $this->assertStringContainsString('esm',
            app('mailer')->getSymfonyTransport()->messages()[0]->toString()
        );

        $mailable = new Mailable;
        $mailable->to($recipient);

        $this->assertSame($recipient->email_locale, $mailable->locale);
    }

    public function testLocaleIsSentWithSelectedLocaleOverridingModelPreferredLocale()
    {
        $recipient = new TestEmailLocaleUser([
            'email' => 'test@mail.com',
            'email_locale' => 'en',
        ]);

        Mail::to($recipient)->locale('ar')->send(new TestMail);

        $this->assertStringContainsString('esm',
            app('mailer')->getSymfonyTransport()->messages()[0]->toString()
        );
    }

    public function testLocaleIsSentWithModelPreferredLocaleWillIgnorePreferredLocaleOfTheCcRecipient()
    {
        $toRecipient = new TestEmailLocaleUser([
            'email' => 'test@mail.com',
            'email_locale' => 'ar',
        ]);

        $ccRecipient = new TestEmailLocaleUser([
            'email' => 'test.cc@mail.com',
            'email_locale' => 'en',
        ]);

        Mail::to($toRecipient)->cc($ccRecipient)->send(new TestMail);

        $this->assertStringContainsString('esm',
            app('mailer')->getSymfonyTransport()->messages()[0]->toString()
        );
    }

    public function testLocaleIsNotSentWithModelPreferredLocaleWhenThereAreMultipleRecipients()
    {
        $recipients = [
            new TestEmailLocaleUser([
                'email' => 'test@mail.com',
                'email_locale' => 'ar',
            ]),
            new TestEmailLocaleUser([
                'email' => 'test.2@mail.com',
                'email_locale' => 'ar',
            ]),
        ];

        Mail::to($recipients)->send(new TestMail);

        $this->assertStringContainsString('name',
            app('mailer')->getSymfonyTransport()->messages()[0]->toString()
        );
    }

    public function testLocaleIsSetBackToDefaultAfterMailSent()
    {
        Mail::to('test@mail.com')->locale('ar')->send(new TestMail);
        Mail::to('test@mail.com')->send(new TestMail);

        $this->assertSame('en', app('translator')->getLocale());

        $this->assertStringContainsString('esm',
            app('mailer')->getSymfonyTransport()->messages()[0]->toString()
        );

        $this->assertStringContainsString('name',
            app('mailer')->getSymfonyTransport()->messages()[1]->toString()
        );
    }
}

class TestMail extends Mailable
{
    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->view('view');
    }
}

class TestEmailLocaleUser extends Model implements HasLocalePreference
{
    protected $fillable = [
        'email',
        'email_locale',
    ];

    public function preferredLocale()
    {
        return $this->email_locale;
    }
}

class TimestampTestMail extends Mailable
{
    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->view('timestamp');
    }
}
