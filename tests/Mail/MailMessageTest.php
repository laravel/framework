<?php

namespace Illuminate\Tests\Mail;

use Mockery as m;
use Illuminate\Mail\Message;
use PHPUnit\Framework\TestCase;

class MailMessageTest extends TestCase
{
    /**
     * @var \Mockery::mock
     */
    protected $swift;

    /**
     * @var \Illuminate\Mail\Message
     */
    protected $message;

    public function setUp(): void
    {
        parent::setUp();

        $this->swift = m::mock(\Swift_Mime_Message::class);
        $this->message = new Message($this->swift);
    }

    public function tearDown(): void
    {
        m::close();
    }

    public function testFromMethod(): void
    {
        $this->swift->shouldReceive('setFrom')->once()->with('foo@bar.baz', 'Foo');
        $this->assertInstanceOf(Message::class, $this->message->from('foo@bar.baz', 'Foo'));
    }

    public function testSenderMethod(): void
    {
        $this->swift->shouldReceive('setSender')->once()->with('foo@bar.baz', 'Foo');
        $this->assertInstanceOf(Message::class, $this->message->sender('foo@bar.baz', 'Foo'));
    }

    public function testReturnPathMethod(): void
    {
        $this->swift->shouldReceive('setReturnPath')->once()->with('foo@bar.baz');
        $this->assertInstanceOf(Message::class, $this->message->returnPath('foo@bar.baz'));
    }

    public function testToMethod(): void
    {
        $this->swift->shouldReceive('addTo')->once()->with('foo@bar.baz', 'Foo');
        $this->assertInstanceOf(Message::class, $this->message->to('foo@bar.baz', 'Foo', false));
    }

    public function testToMethodWithOverride(): void
    {
        $this->swift->shouldReceive('setTo')->once()->with('foo@bar.baz', 'Foo');
        $this->assertInstanceOf(Message::class, $this->message->to('foo@bar.baz', 'Foo', true));
    }

    public function testCcMethod(): void
    {
        $this->swift->shouldReceive('addCc')->once()->with('foo@bar.baz', 'Foo');
        $this->assertInstanceOf(Message::class, $this->message->cc('foo@bar.baz', 'Foo'));
    }

    public function testBccMethod(): void
    {
        $this->swift->shouldReceive('addBcc')->once()->with('foo@bar.baz', 'Foo');
        $this->assertInstanceOf(Message::class, $this->message->bcc('foo@bar.baz', 'Foo'));
    }

    public function testReplyToMethod(): void
    {
        $this->swift->shouldReceive('addReplyTo')->once()->with('foo@bar.baz', 'Foo');
        $this->assertInstanceOf(Message::class, $this->message->replyTo('foo@bar.baz', 'Foo'));
    }

    public function testSubjectMethod(): void
    {
        $this->swift->shouldReceive('setSubject')->once()->with('foo');
        $this->assertInstanceOf(Message::class, $this->message->subject('foo'));
    }

    public function testPriorityMethod(): void
    {
        $this->swift->shouldReceive('setPriority')->once()->with(1);
        $this->assertInstanceOf(Message::class, $this->message->priority(1));
    }

    public function testGetSwiftMessageMethod(): void
    {
        $this->assertInstanceOf(\Swift_Mime_Message::class, $this->message->getSwiftMessage());
    }

    public function testBasicAttachment(): void
    {
        $swift = m::mock('stdClass');
        $message = $this->getMockBuilder('Illuminate\Mail\Message')->setMethods(['createAttachmentFromPath'])->setConstructorArgs([$swift])->getMock();
        $attachment = m::mock('stdClass');
        $message->expects($this->once())->method('createAttachmentFromPath')->with($this->equalTo('foo.jpg'))->will($this->returnValue($attachment));
        $swift->shouldReceive('attach')->once()->with($attachment);
        $attachment->shouldReceive('setContentType')->once()->with('image/jpeg');
        $attachment->shouldReceive('setFilename')->once()->with('bar.jpg');
        $message->attach('foo.jpg', ['mime' => 'image/jpeg', 'as' => 'bar.jpg']);
    }

    public function testDataAttachment(): void
    {
        $swift = m::mock('stdClass');
        $message = $this->getMockBuilder('Illuminate\Mail\Message')->setMethods(['createAttachmentFromData'])->setConstructorArgs([$swift])->getMock();
        $attachment = m::mock('stdClass');
        $message->expects($this->once())->method('createAttachmentFromData')->with($this->equalTo('foo'), $this->equalTo('name'))->will($this->returnValue($attachment));
        $swift->shouldReceive('attach')->once()->with($attachment);
        $attachment->shouldReceive('setContentType')->once()->with('image/jpeg');
        $message->attachData('foo', 'name', ['mime' => 'image/jpeg']);
    }
}
