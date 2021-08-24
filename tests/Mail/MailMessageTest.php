<?php

namespace Illuminate\Tests\Mail;

use Illuminate\Mail\Message;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

class MailMessageTest extends TestCase
{
    /**
     * @var \Illuminate\Mail\Message
     */
    protected $message;

    protected function setUp(): void
    {
        parent::setUp();

        $this->message = new Message(new Email());
    }

    public function testFromMethod()
    {
        $this->assertInstanceOf(Message::class, $message = $this->message->from('foo@bar.baz', 'Foo'));
        $this->assertEquals(new Address('foo@bar.baz', 'Foo'), $message->getSymfonyMessage()->getFrom()[0]);
    }

    public function testSenderMethod()
    {
        $this->assertInstanceOf(Message::class, $message = $this->message->sender('foo@bar.baz', 'Foo'));
        $this->assertEquals(new Address('foo@bar.baz', 'Foo'), $message->getSymfonyMessage()->getSender());
    }

    public function testReturnPathMethod()
    {
        $this->assertInstanceOf(Message::class, $message = $this->message->returnPath('foo@bar.baz'));
        $this->assertEquals(new Address('foo@bar.baz'), $message->getSymfonyMessage()->getReturnPath());
    }

    public function testToMethod()
    {
        $this->assertInstanceOf(Message::class, $message = $this->message->to('foo@bar.baz', 'Foo', false));
        $this->assertEquals(new Address('foo@bar.baz', 'Foo'), $message->getSymfonyMessage()->getTo()[0]);
    }

    public function testToMethodWithOverride()
    {
        $this->assertInstanceOf(Message::class, $message = $this->message->to('foo@bar.baz', 'Foo', true));
        $this->assertEquals(new Address('foo@bar.baz', 'Foo'), $message->getSymfonyMessage()->getTo()[0]);
    }

    public function testCcMethod()
    {
        $this->assertInstanceOf(Message::class, $message = $this->message->cc('foo@bar.baz', 'Foo'));
        $this->assertEquals(new Address('foo@bar.baz', 'Foo'), $message->getSymfonyMessage()->getCc()[0]);
    }

    public function testBccMethod()
    {
        $this->assertInstanceOf(Message::class, $message = $this->message->bcc('foo@bar.baz', 'Foo'));
        $this->assertEquals(new Address('foo@bar.baz', 'Foo'), $message->getSymfonyMessage()->getBcc()[0]);
    }

    public function testReplyToMethod()
    {
        $this->assertInstanceOf(Message::class, $message = $this->message->replyTo('foo@bar.baz', 'Foo'));
        $this->assertEquals(new Address('foo@bar.baz', 'Foo'), $message->getSymfonyMessage()->getReplyTo()[0]);
    }

    public function testSubjectMethod()
    {
        $this->assertInstanceOf(Message::class, $message = $this->message->subject('foo'));
        $this->assertEquals('foo', $message->getSymfonyMessage()->getSubject());
    }

    public function testPriorityMethod()
    {
        $this->assertInstanceOf(Message::class, $message = $this->message->priority(1));
        $this->assertEquals(1, $message->getSymfonyMessage()->getPriority());
    }

    public function testBasicAttachment()
    {
        $message = new Message(new Email());
        $message->attach('foo.jpg', ['as' => 'foo.jpg', 'mime' => 'image/jpeg']);
    }

    public function testDataAttachment()
    {
        $message = new Message(new Email());
        $message->attachData('foo', 'foo.jpg', ['mime' => 'image/jpeg']);

        $this->assertEquals('foo', $message->getSymfonyMessage()->getAttachments()[0]->getBody());
    }
}
