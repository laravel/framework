<?php

namespace Illuminate\Tests\Mail;

use Illuminate\Mail\Mailable;
use PHPUnit\Framework\TestCase;

class MailMailableTest extends TestCase
{
    public function testMailableSetsRecipientsCorrectly()
    {
        $mailable = new WelcomeMailableStub;
        $mailable->to('taylor@laravel.com');
        $this->assertEquals([['name' => null, 'address' => 'taylor@laravel.com']], $mailable->to);
        $this->assertTrue($mailable->hasTo('taylor@laravel.com'));

        $mailable = new WelcomeMailableStub;
        $mailable->to('taylor@laravel.com', 'Taylor Otwell');
        $this->assertEquals([['name' => 'Taylor Otwell', 'address' => 'taylor@laravel.com']], $mailable->to);
        $this->assertTrue($mailable->hasTo('taylor@laravel.com', 'Taylor Otwell'));
        $this->assertTrue($mailable->hasTo('taylor@laravel.com'));

        $mailable = new WelcomeMailableStub;
        $mailable->to(['taylor@laravel.com']);
        $this->assertEquals([['name' => null, 'address' => 'taylor@laravel.com']], $mailable->to);
        $this->assertTrue($mailable->hasTo('taylor@laravel.com'));
        $this->assertFalse($mailable->hasTo('taylor@laravel.com', 'Taylor Otwell'));

        $mailable = new WelcomeMailableStub;
        $mailable->to([['name' => 'Taylor Otwell', 'email' => 'taylor@laravel.com']]);
        $this->assertEquals([['name' => 'Taylor Otwell', 'address' => 'taylor@laravel.com']], $mailable->to);
        $this->assertTrue($mailable->hasTo('taylor@laravel.com', 'Taylor Otwell'));
        $this->assertTrue($mailable->hasTo('taylor@laravel.com'));

        $mailable = new WelcomeMailableStub;
        $mailable->to(new MailableTestUserStub);
        $this->assertEquals([['name' => 'Taylor Otwell', 'address' => 'taylor@laravel.com']], $mailable->to);
        $this->assertTrue($mailable->hasTo(new MailableTestUserStub));
        $this->assertTrue($mailable->hasTo('taylor@laravel.com'));

        $mailable = new WelcomeMailableStub;
        $mailable->to(collect([new MailableTestUserStub]));
        $this->assertEquals([['name' => 'Taylor Otwell', 'address' => 'taylor@laravel.com']], $mailable->to);
        $this->assertTrue($mailable->hasTo(new MailableTestUserStub));
        $this->assertTrue($mailable->hasTo('taylor@laravel.com'));

        $mailable = new WelcomeMailableStub;
        $mailable->to(collect([new MailableTestUserStub, new MailableTestUserStub]));
        $this->assertEquals([
            ['name' => 'Taylor Otwell', 'address' => 'taylor@laravel.com'],
            ['name' => 'Taylor Otwell', 'address' => 'taylor@laravel.com'],
        ], $mailable->to);
        $this->assertTrue($mailable->hasTo(new MailableTestUserStub));
        $this->assertTrue($mailable->hasTo('taylor@laravel.com'));
    }

    public function testMailableSetsReplyToCorrectly()
    {
        $mailable = new WelcomeMailableStub;
        $mailable->replyTo('taylor@laravel.com');
        $this->assertEquals([['name' => null, 'address' => 'taylor@laravel.com']], $mailable->replyTo);
        $this->assertTrue($mailable->hasReplyTo('taylor@laravel.com'));

        $mailable = new WelcomeMailableStub;
        $mailable->replyTo('taylor@laravel.com', 'Taylor Otwell');
        $this->assertEquals([['name' => 'Taylor Otwell', 'address' => 'taylor@laravel.com']], $mailable->replyTo);
        $this->assertTrue($mailable->hasReplyTo('taylor@laravel.com', 'Taylor Otwell'));
        $this->assertTrue($mailable->hasReplyTo('taylor@laravel.com'));

        $mailable = new WelcomeMailableStub;
        $mailable->replyTo(['taylor@laravel.com']);
        $this->assertEquals([['name' => null, 'address' => 'taylor@laravel.com']], $mailable->replyTo);
        $this->assertTrue($mailable->hasReplyTo('taylor@laravel.com'));
        $this->assertFalse($mailable->hasReplyTo('taylor@laravel.com', 'Taylor Otwell'));

        $mailable = new WelcomeMailableStub;
        $mailable->replyTo([['name' => 'Taylor Otwell', 'email' => 'taylor@laravel.com']]);
        $this->assertEquals([['name' => 'Taylor Otwell', 'address' => 'taylor@laravel.com']], $mailable->replyTo);
        $this->assertTrue($mailable->hasReplyTo('taylor@laravel.com', 'Taylor Otwell'));
        $this->assertTrue($mailable->hasReplyTo('taylor@laravel.com'));

        $mailable = new WelcomeMailableStub;
        $mailable->replyTo(new MailableTestUserStub);
        $this->assertEquals([['name' => 'Taylor Otwell', 'address' => 'taylor@laravel.com']], $mailable->replyTo);
        $this->assertTrue($mailable->hasReplyTo(new MailableTestUserStub));
        $this->assertTrue($mailable->hasReplyTo('taylor@laravel.com'));

        $mailable = new WelcomeMailableStub;
        $mailable->replyTo(collect([new MailableTestUserStub]));
        $this->assertEquals([['name' => 'Taylor Otwell', 'address' => 'taylor@laravel.com']], $mailable->replyTo);
        $this->assertTrue($mailable->hasReplyTo(new MailableTestUserStub));
        $this->assertTrue($mailable->hasReplyTo('taylor@laravel.com'));

        $mailable = new WelcomeMailableStub;
        $mailable->replyTo(collect([new MailableTestUserStub, new MailableTestUserStub]));
        $this->assertEquals([
            ['name' => 'Taylor Otwell', 'address' => 'taylor@laravel.com'],
            ['name' => 'Taylor Otwell', 'address' => 'taylor@laravel.com'],
        ], $mailable->replyTo);
        $this->assertTrue($mailable->hasReplyTo(new MailableTestUserStub));
        $this->assertTrue($mailable->hasReplyTo('taylor@laravel.com'));
    }

    public function testMailableBuildsViewData()
    {
        $mailable = new WelcomeMailableStub;

        $mailable->build();

        $expected = [
            'first_name' => 'Taylor',
            'last_name' => 'Otwell',
            'framework' => 'Laravel',
        ];

        $this->assertSame($expected, $mailable->buildViewData());
    }
}

class WelcomeMailableStub extends Mailable
{
    public $framework = 'Laravel';

    protected $version = '5.3';

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $this->with('first_name', 'Taylor')
             ->withLastName('Otwell');
    }
}

class MailableTestUserStub
{
    public $name = 'Taylor Otwell';
    public $email = 'taylor@laravel.com';
}
