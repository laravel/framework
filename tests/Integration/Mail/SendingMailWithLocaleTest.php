<?php

namespace Illuminate\Tests\Integration\Mail;

use Illuminate\Contracts\Translation\HasLocalePreference;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\LocaleUpdated;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\View;
use Illuminate\Testing\Assert;
use Orchestra\Testbench\TestCase;

class SendingMailWithLocaleTest extends TestCase
{
    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('mail.driver', 'array');

        $app['config']->set('app.locale', 'en');

        View::addLocation(__DIR__.'/Fixtures');

        app('translator')->setLoaded([
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
            app('mailer')->getSwiftMailer()->getTransport()->messages()[0]->getBody()
        );
    }

    public function testMailIsSentWithSelectedLocale()
    {
        Mail::to('test@mail.com')->locale('ar')->send(new TestMail);

        $this->assertStringContainsString('esm',
            app('mailer')->getSwiftMailer()->getTransport()->messages()[0]->getBody()
        );
    }

    public function testMailIsSentWithLocaleFromMailable()
    {
        $mailable = new TestMail;
        $mailable->locale('ar');

        Mail::to('test@mail.com')->send($mailable);

        $this->assertStringContainsString('esm',
            app('mailer')->getSwiftMailer()->getTransport()->messages()[0]->getBody()
        );
    }

    public function testMailIsSentWithLocaleUpdatedListenersCalled()
    {
        Carbon::setTestNow('2018-04-01');

        Event::listen(LocaleUpdated::class, function ($event) {
            Carbon::setLocale($event->locale);
        });

        Mail::to('test@mail.com')->locale('es')->send(new TimestampTestMail);

        Assert::assertMatchesRegularExpression('/nombre (en|dentro de) (un|1) día/',
            app('mailer')->getSwiftMailer()->getTransport()->messages()[0]->getBody()
        );

        $this->assertSame('en', Carbon::getLocale());
    }

    public function testLocaleIsSentWithModelPreferredLocale()
    {
        $recipient = new TestEmailLocaleUser([
            'email' => 'test@mail.com',
            'email_locale' => 'ar',
        ]);

        Mail::to($recipient)->send(new TestMail);

        $this->assertStringContainsString('esm',
            app('mailer')->getSwiftMailer()->getTransport()->messages()[0]->getBody()
        );
    }

    public function testLocaleIsSentWithSelectedLocaleOverridingModelPreferredLocale()
    {
        $recipient = new TestEmailLocaleUser([
            'email' => 'test@mail.com',
            'email_locale' => 'en',
        ]);

        Mail::to($recipient)->locale('ar')->send(new TestMail);

        $this->assertStringContainsString('esm',
            app('mailer')->getSwiftMailer()->getTransport()->messages()[0]->getBody()
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
            app('mailer')->getSwiftMailer()->getTransport()->messages()[0]->getBody()
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
            app('mailer')->getSwiftMailer()->getTransport()->messages()[0]->getBody()
        );
    }

    public function testLocaleIsSetBackToDefaultAfterMailSent()
    {
        Mail::to('test@mail.com')->locale('ar')->send(new TestMail);
        Mail::to('test@mail.com')->send(new TestMail);

        $this->assertSame('en', app('translator')->getLocale());

        $this->assertStringContainsString('esm',
            app('mailer')->getSwiftMailer()->getTransport()->messages()[0]->getBody()
        );

        $this->assertStringContainsString('name',
            app('mailer')->getSwiftMailer()->getTransport()->messages()[1]->getBody()
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
