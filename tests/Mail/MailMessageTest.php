<?php

namespace Illuminate\Tests\Mail;

use Illuminate\Mail\Message;
use Mockery as m;
use PHPUnit\Framework\TestCase;
use stdClass;
use Swift_Mime_Message;

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

    protected function setUp(): void
    {
        parent::setUp();

        $this->swift = m::mock(Swift_Mime_Message::class);
        $this->message = new Message($this->swift);
    }

    protected function tearDown(): void
    {
        m::close();
    }

    public function testFromMethod()
    {
        $this->swift->shouldReceive('setFrom')->once()->with('foo@bar.baz', 'Foo');
        $this->assertInstanceOf(Message::class, $this->message->from('foo@bar.baz', 'Foo'));
    }

    public function testSenderMethod()
    {
        $this->swift->shouldReceive('setSender')->once()->with('foo@bar.baz', 'Foo');
        $this->assertInstanceOf(Message::class, $this->message->sender('foo@bar.baz', 'Foo'));
    }

    public function testReturnPathMethod()
    {
        $this->swift->shouldReceive('setReturnPath')->once()->with('foo@bar.baz');
        $this->assertInstanceOf(Message::class, $this->message->returnPath('foo@bar.baz'));
    }

    public function testToMethod()
    {
        $this->swift->shouldReceive('addTo')->once()->with('foo@bar.baz', 'Foo');
        $this->assertInstanceOf(Message::class, $this->message->to('foo@bar.baz', 'Foo', false));
    }

    public function testToMethodWithOverride()
    {
        $this->swift->shouldReceive('setTo')->once()->with('foo@bar.baz', 'Foo');
        $this->assertInstanceOf(Message::class, $this->message->to('foo@bar.baz', 'Foo', true));
    }

    public function testCcMethod()
    {
        $this->swift->shouldReceive('addCc')->once()->with('foo@bar.baz', 'Foo');
        $this->assertInstanceOf(Message::class, $this->message->cc('foo@bar.baz', 'Foo'));
    }

    public function testBccMethod()
    {
        $this->swift->shouldReceive('addBcc')->once()->with('foo@bar.baz', 'Foo');
        $this->assertInstanceOf(Message::class, $this->message->bcc('foo@bar.baz', 'Foo'));
    }

    public function testReplyToMethod()
    {
        $this->swift->shouldReceive('addReplyTo')->once()->with('foo@bar.baz', 'Foo');
        $this->assertInstanceOf(Message::class, $this->message->replyTo('foo@bar.baz', 'Foo'));
    }

    public function testSubjectMethod()
    {
        $this->swift->shouldReceive('setSubject')->once()->with('foo');
        $this->assertInstanceOf(Message::class, $this->message->subject('foo'));
    }

    public function testPriorityMethod()
    {
        $this->swift->shouldReceive('setPriority')->once()->with(1);
        $this->assertInstanceOf(Message::class, $this->message->priority(1));
    }

    public function testGetSwiftMessageMethod()
    {
        $this->assertInstanceOf(Swift_Mime_Message::class, $this->message->getSwiftMessage());
    }

    public function testBasicAttachment()
    {
        $swift = m::mock(stdClass::class);
        $message = $this->getMockBuilder(Message::class)->onlyMethods(['createAttachmentFromPath'])->setConstructorArgs([$swift])->getMock();
        $attachment = m::mock(stdClass::class);
        $message->expects($this->once())->method('createAttachmentFromPath')->with($this->equalTo('foo.jpg'))->willReturn($attachment);
        $swift->shouldReceive('attach')->once()->with($attachment);
        $attachment->shouldReceive('setContentType')->once()->with('image/jpeg');
        $attachment->shouldReceive('setFilename')->once()->with('bar.jpg');
        $message->attach('foo.jpg', ['mime' => 'image/jpeg', 'as' => 'bar.jpg']);
    }

    public function testDataAttachment()
    {
        $swift = m::mock(stdClass::class);
        $message = $this->getMockBuilder(Message::class)->onlyMethods(['createAttachmentFromData'])->setConstructorArgs([$swift])->getMock();
        $attachment = m::mock(stdClass::class);
        $message->expects($this->once())->method('createAttachmentFromData')->with($this->equalTo('foo'), $this->equalTo('name'))->willReturn($attachment);
        $swift->shouldReceive('attach')->once()->with($attachment);
        $attachment->shouldReceive('setContentType')->once()->with('image/jpeg');
        $message->attachData('foo', 'name', ['mime' => 'image/jpeg']);
    }
}
