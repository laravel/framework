<?php

namespace Illuminate\Tests\Integration\Mail;

use Mockery;
use Illuminate\Mail\Mailable;
use Orchestra\Testbench\TestCase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\View;

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
