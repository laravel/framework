<?php

namespace Illuminate\Tests\Mail;

use stdClass;
use Mockery as m;
use Illuminate\Mail\Message;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\NamedAddress;

class MailMessageTest extends TestCase
{
    /**
     * @var \Mockery::mock
     */
    protected $email;

    /**
     * @var \Symfony\Component\Mime\Email
     */
    protected $message;

    protected function setUp(): void
    {
        parent::setUp();

        $this->email = m::mock(Email::class);
        $this->message = new Message($this->email);
    }

    protected function tearDown(): void
    {
        m::close();
    }

    public function testFromMethod()
    {
        $this->email->shouldReceive('from')->once()->andReturnUsing(function ($arg) {
            $this->assertEquals(new NamedAddress('foo@bar.baz', 'Foo'), $arg);
        });

        $this->assertInstanceOf(Message::class, $this->message->from('foo@bar.baz', 'Foo'));
    }

    public function testSenderMethod()
    {
        $this->email->shouldReceive('sender')->once()->with('foo@bar.baz');
        $this->assertInstanceOf(Message::class, $this->message->sender('foo@bar.baz'));
    }

    public function testReturnPathMethod()
    {
        $this->email->shouldReceive('returnPath')->once()->with('foo@bar.baz');
        $this->assertInstanceOf(Message::class, $this->message->returnPath('foo@bar.baz'));
    }

    public function testToMethod()
    {
        $this->email->shouldReceive('addTo')->once()->andReturnUsing(function ($arg) {
            $this->assertEquals(new NamedAddress('foo@bar.baz', 'Foo'), $arg);
        });
        $this->assertInstanceOf(Message::class, $this->message->to('foo@bar.baz', 'Foo', false));
    }

    public function testToMethodWithOverride()
    {
        $this->email->shouldReceive('to')->once()->andReturnUsing(function ($arg) {
            $this->assertEquals(new NamedAddress('foo@bar.baz', 'Foo'), $arg);
        });
        $this->assertInstanceOf(Message::class, $this->message->to('foo@bar.baz', 'Foo', true));
    }

    public function testCcMethod()
    {
        $this->email->shouldReceive('addCc')->once()->andReturnUsing(function ($arg) {
            $this->assertEquals(new NamedAddress('foo@bar.baz', 'Foo'), $arg);
        });
        $this->assertInstanceOf(Message::class, $this->message->cc('foo@bar.baz', 'Foo'));
    }

    public function testBccMethod()
    {
        $this->email->shouldReceive('addBcc')->once()->andReturnUsing(function ($arg) {
            $this->assertEquals(new NamedAddress('foo@bar.baz', 'Foo'), $arg);
        });
        $this->assertInstanceOf(Message::class, $this->message->bcc('foo@bar.baz', 'Foo'));
    }

    public function testReplyToMethod()
    {
        $this->email->shouldReceive('addReplyTo')->once()->andReturnUsing(function ($arg) {
            $this->assertEquals(new NamedAddress('foo@bar.baz', 'Foo'), $arg);
        });
        $this->assertInstanceOf(Message::class, $this->message->replyTo('foo@bar.baz', 'Foo'));
    }

    public function testSubjectMethod()
    {
        $this->email->shouldReceive('subject')->once()->with('foo');
        $this->assertInstanceOf(Message::class, $this->message->subject('foo'));
    }

    public function testPriorityMethod()
    {
        $this->email->shouldReceive('priority')->once()->with(1);
        $this->assertInstanceOf(Message::class, $this->message->priority(1));
    }

    public function testGetSymfonyEmailMethod()
    {
        $this->assertInstanceOf(Email::class, $this->message->getSymfonyEmail());
    }

    public function testBasicAttachment()
    {
        $this->email->shouldReceive('attachFromPath')->once()->with(
            'foo.jpg',
            'bar.jpg',
            'image/jpeg'
        );
        $this->assertInstanceOf(Message::class, $this->message->attach('foo.jpg', ['mime' => 'image/jpeg', 'as' => 'bar.jpg']));
    }

    public function testDataAttachment()
    {
        $this->email->shouldReceive('attach')->with('foo', 'name', 'image/jpeg');
        $this->assertInstanceOf(Message::class, $this->message->attachData('foo', 'name', ['mime' => 'image/jpeg']));
    }

    public function testCreateAddress()
    {
        // Named addresses
        $this->assertEquals([
            new NamedAddress('foo@bar.baz', 'Foo')
        ], $this->message->createAddress('foo@bar.baz', 'Foo'));

        // Un-named addresses
        $this->assertEquals([
            new Address('foo@bar.baz'),
            new Address('foo@bar.com'),
        ], $this->message->createAddress(['foo@bar.baz', 'foo@bar.com']));

        // Un-named named addresses
        $this->assertEquals([
            new Address('foo@bar.baz'),
            new Address('foo@bar.com'),
        ], $this->message->createAddress(['foo@bar.baz', 'foo@bar.com'], 'Foo'));
    }
}
