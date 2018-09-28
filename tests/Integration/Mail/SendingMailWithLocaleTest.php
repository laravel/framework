<?php

namespace Illuminate\Tests\Integration\Mail;

use Mockery;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Carbon;
use Orchestra\Testbench\TestCase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Event;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\LocaleUpdated;
use Illuminate\Contracts\Translation\HasLocalePreference;

/**
 * @group integration
 */
class SendingMailWithLocaleTest extends TestCase
{
    public function tearDown()
    {
        parent::tearDown();

        Mockery::close();
    }

    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('app.debug', 'true');

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

    public function setUp()
    {
        parent::setUp();
    }

    public function test_mail_is_sent_with_default_locale()
    {
        Mail::to('test@mail.com')->send(new TestMail());

        $this->assertContains('name',
            app('swift.transport')->messages()[0]->getBody()
        );
    }

    public function test_mail_is_sent_with_selected_locale()
    {
        Mail::to('test@mail.com')->locale('ar')->send(new TestMail());

        $this->assertContains('esm',
            app('swift.transport')->messages()[0]->getBody()
        );
    }

    public function test_mail_is_sent_with_locale_updated_listeners_called()
    {
        Carbon::setTestNow(Carbon::parse('2018-04-01'));

        Event::listen(LocaleUpdated::class, function ($event) {
            Carbon::setLocale($event->locale);
        });

        Mail::to('test@mail.com')->locale('es')->send(new TimestampTestMail);

        $this->assertContains('nombre dentro de 1 día',
            app('swift.transport')->messages()[0]->getBody()
        );

        $this->assertEquals('en', Carbon::getLocale());
    }

    public function test_locale_is_sent_with_model_preferred_locale()
    {
        $recipient = new TestEmailLocaleUser([
            'email' => 'test@mail.com',
            'email_locale' => 'ar',
        ]);

        Mail::to($recipient)->send(new TestMail());

        $this->assertContains('esm',
            app('swift.transport')->messages()[0]->getBody()
        );
    }

    public function test_locale_is_sent_with_selected_locale_overriding_model_preferred_locale()
    {
        $recipient = new TestEmailLocaleUser([
            'email' => 'test@mail.com',
            'email_locale' => 'en',
        ]);

        Mail::to($recipient)->locale('ar')->send(new TestMail());

        $this->assertContains('esm',
            app('swift.transport')->messages()[0]->getBody()
        );
    }

    public function test_locale_is_sent_with_model_preferred_locale_will_ignore_preferred_locale_of_the_cc_recipient()
    {
        $toRecipient = new TestEmailLocaleUser([
            'email' => 'test@mail.com',
            'email_locale' => 'ar',
        ]);

        $ccRecipient = new TestEmailLocaleUser([
            'email' => 'test.cc@mail.com',
            'email_locale' => 'en',
        ]);

        Mail::to($toRecipient)->cc($ccRecipient)->send(new TestMail());

        $this->assertContains('esm',
            app('swift.transport')->messages()[0]->getBody()
        );
    }

    public function test_locale_is_not_sent_with_model_preferred_locale_when_there_are_multiple_recipients()
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

        Mail::to($recipients)->send(new TestMail());

        $this->assertContains('name',
            app('swift.transport')->messages()[0]->getBody()
        );
    }

    public function test_locale_is_set_back_to_default_after_mail_sent()
    {
        Mail::to('test@mail.com')->locale('ar')->send(new TestMail());
        Mail::to('test@mail.com')->send(new TestMail());

        $this->assertEquals('en', app('translator')->getLocale());

        $this->assertContains('esm',
            app('swift.transport')->messages()[0]->getBody()
        );

        $this->assertContains('name',
            app('swift.transport')->messages()[1]->getBody()
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
