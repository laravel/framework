<?php

use Mockery as m;
use Illuminate\Mail\Message;

class MailMessageTest extends PHPUnit_Framework_TestCase
{
    public function tearDown()
    {
        m::close();
    }

    public function testFromMethod()
    {
        $swift = m::mock(Swift_Mime_Message::class);
        $message = new Message($swift);
        $swift->shouldReceive('setFrom')->once()->with('foo@bar.baz', 'Foo');
        $this->assertInstanceOf(Message::class, $message->from('foo@bar.baz', 'Foo'));
    }

    public function testSenderMethod()
    {
        $swift = m::mock(Swift_Mime_Message::class);
        $message = new Message($swift);
        $swift->shouldReceive('setSender')->once()->with('foo@bar.baz', 'Foo');
        $this->assertInstanceOf(Message::class, $message->sender('foo@bar.baz', 'Foo'));
    }

    public function testReturnPathMethod()
    {
        $swift = m::mock(Swift_Mime_Message::class);
        $message = new Message($swift);
        $swift->shouldReceive('setReturnPath')->once()->with('foo@bar.baz');
        $this->assertInstanceOf(Message::class, $message->returnPath('foo@bar.baz'));
    }

    public function testToMethod()
    {
        $swift = m::mock(Swift_Mime_Message::class);
        $message = new Message($swift);
        $swift->shouldReceive('addTo')->once()->with('foo@bar.baz', 'Foo');
        $this->assertInstanceOf(Message::class, $message->to('foo@bar.baz', 'Foo', false));
    }

    public function testToMethodWithOverride()
    {
        $swift = m::mock(Swift_Mime_Message::class);
        $message = new Message($swift);
        $swift->shouldReceive('setTo')->once()->with('foo@bar.baz', 'Foo');
        $this->assertInstanceOf(Message::class, $message->to('foo@bar.baz', 'Foo', true));
    }

    public function testCcMethod()
    {
        $swift = m::mock(Swift_Mime_Message::class);
        $message = new Message($swift);
        $swift->shouldReceive('addCc')->once()->with('foo@bar.baz', 'Foo');
        $this->assertInstanceOf(Message::class, $message->cc('foo@bar.baz', 'Foo'));
    }

    public function testBccMethod()
    {
        $swift = m::mock(Swift_Mime_Message::class);
        $message = new Message($swift);
        $swift->shouldReceive('addBcc')->once()->with('foo@bar.baz', 'Foo');
        $this->assertInstanceOf(Message::class, $message->bcc('foo@bar.baz', 'Foo'));
    }

    public function testReplyToMethod()
    {
        $swift = m::mock(Swift_Mime_Message::class);
        $message = new Message($swift);
        $swift->shouldReceive('addReplyTo')->once()->with('foo@bar.baz', 'Foo');
        $this->assertInstanceOf(Message::class, $message->replyTo('foo@bar.baz', 'Foo'));
    }

    public function testSubjectMethod()
    {
        $swift = m::mock(Swift_Mime_Message::class);
        $message = new Message($swift);
        $swift->shouldReceive('setSubject')->once()->with('foo');
        $this->assertInstanceOf(Message::class, $message->subject('foo'));
    }

    public function testPriorityMethod()
    {
        $swift = m::mock(Swift_Mime_Message::class);
        $message = new Message($swift);
        $swift->shouldReceive('setPriority')->once()->with(1);
        $this->assertInstanceOf(Message::class, $message->priority(1));
    }

    public function testGetSwiftMessageMethod()
    {
        $swift = m::mock(Swift_Mime_Message::class);
        $message = new Message($swift);
        $this->assertInstanceOf(Swift_Mime_Message::class, $message->getSwiftMessage());
    }

    public function testBasicAttachment()
    {
        $swift = m::mock('StdClass');
        $message = $this->getMock('Illuminate\Mail\Message', ['createAttachmentFromPath'], [$swift]);
        $attachment = m::mock('StdClass');
        $message->expects($this->once())->method('createAttachmentFromPath')->with($this->equalTo('foo.jpg'))->will($this->returnValue($attachment));
        $swift->shouldReceive('attach')->once()->with($attachment);
        $attachment->shouldReceive('setContentType')->once()->with('image/jpeg');
        $attachment->shouldReceive('setFilename')->once()->with('bar.jpg');
        $message->attach('foo.jpg', ['mime' => 'image/jpeg', 'as' => 'bar.jpg']);
    }

    public function testDataAttachment()
    {
        $swift = m::mock('StdClass');
        $message = $this->getMock('Illuminate\Mail\Message', ['createAttachmentFromData'], [$swift]);
        $attachment = m::mock('StdClass');
        $message->expects($this->once())->method('createAttachmentFromData')->with($this->equalTo('foo'), $this->equalTo('name'))->will($this->returnValue($attachment));
        $swift->shouldReceive('attach')->once()->with($attachment);
        $attachment->shouldReceive('setContentType')->once()->with('image/jpeg');
        $message->attachData('foo', 'name', ['mime' => 'image/jpeg']);
    }
}
