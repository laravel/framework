<?php

namespace Illuminate\Tests\Mail;

use Illuminate\Contracts\Mail\Attachable;
use Illuminate\Mail\Attachment;
use Illuminate\Mail\Message;
use Illuminate\Support\Str;
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
        $this->assertSame($this->message, $this->message->from('foo@bar.baz', 'Foo'));
        $this->assertEquals(new Address('foo@bar.baz', 'Foo'), $this->message->getSymfonyMessage()->getFrom()[0]);
    }

    public function testSenderMethod()
    {
        $this->assertSame($this->message, $this->message->sender('foo@bar.baz', 'Foo'));
        $this->assertEquals(new Address('foo@bar.baz', 'Foo'), $this->message->getSymfonyMessage()->getSender());
    }

    public function testReturnPathMethod()
    {
        $this->assertSame($this->message, $this->message->returnPath('foo@bar.baz'));
        $this->assertEquals(new Address('foo@bar.baz'), $this->message->getSymfonyMessage()->getReturnPath());
    }

    public function testToMethod()
    {
        $this->assertSame($this->message, $this->message->to('foo@bar.baz', 'Foo'));
        $this->assertEquals(new Address('foo@bar.baz', 'Foo'), $this->message->getSymfonyMessage()->getTo()[0]);

        $this->assertSame($this->message, $this->message->to(['bar@bar.baz' => 'Bar']));
        $this->assertEquals(new Address('bar@bar.baz', 'Bar'), $this->message->getSymfonyMessage()->getTo()[0]);
    }

    public function testToMethodWithOverride()
    {
        $this->assertSame($this->message, $this->message->to('foo@bar.baz', 'Foo', true));
        $this->assertEquals(new Address('foo@bar.baz', 'Foo'), $this->message->getSymfonyMessage()->getTo()[0]);
    }

    public function testCcMethod()
    {
        $this->assertSame($this->message, $this->message->cc('foo@bar.baz', 'Foo'));
        $this->assertEquals(new Address('foo@bar.baz', 'Foo'), $this->message->getSymfonyMessage()->getCc()[0]);
    }

    public function testBccMethod()
    {
        $this->assertSame($this->message, $this->message->bcc('foo@bar.baz', 'Foo'));
        $this->assertEquals(new Address('foo@bar.baz', 'Foo'), $this->message->getSymfonyMessage()->getBcc()[0]);
    }

    public function testReplyToMethod()
    {
        $this->assertSame($this->message, $this->message->replyTo('foo@bar.baz', 'Foo'));
        $this->assertEquals(new Address('foo@bar.baz', 'Foo'), $this->message->getSymfonyMessage()->getReplyTo()[0]);
    }

    public function testSubjectMethod()
    {
        $this->assertSame($this->message, $this->message->subject('foo'));
        $this->assertSame('foo', $this->message->getSymfonyMessage()->getSubject());
    }

    public function testPriorityMethod()
    {
        $this->assertSame($this->message, $this->message->priority(1));
        $this->assertEquals(1, $this->message->getSymfonyMessage()->getPriority());
    }

    public function testBasicAttachment()
    {
        file_put_contents($path = __DIR__.'/foo.jpg', 'expected attachment body');

        $this->message->attach($path, ['as' => 'bar.jpg', 'mime' => 'image/png']);

        $attachment = $this->message->getSymfonyMessage()->getAttachments()[0];
        $headers = $attachment->getPreparedHeaders()->toArray();
        $this->assertSame('expected attachment body', $attachment->getBody());
        $this->assertSame('Content-Type: image/png; name=bar.jpg', $headers[0]);
        $this->assertSame('Content-Transfer-Encoding: base64', $headers[1]);
        $this->assertSame('Content-Disposition: attachment; name=bar.jpg; filename=bar.jpg', $headers[2]);

        unlink($path);
    }

    public function testDataAttachment()
    {
        $this->message->attachData('expected attachment body', 'foo.jpg', ['mime' => 'image/png']);

        $attachment = $this->message->getSymfonyMessage()->getAttachments()[0];
        $headers = $attachment->getPreparedHeaders()->toArray();
        $this->assertSame('expected attachment body', $attachment->getBody());
        $this->assertSame('Content-Type: image/png; name=foo.jpg', $headers[0]);
        $this->assertSame('Content-Transfer-Encoding: base64', $headers[1]);
        $this->assertSame('Content-Disposition: attachment; name=foo.jpg; filename=foo.jpg', $headers[2]);
    }

    public function testItAttachesFilesViaAttachableContractFromPath()
    {
        file_put_contents($path = __DIR__.'/foo.jpg', 'expected attachment body');

        $this->message->attach(new class() implements Attachable
        {
            public function toMailAttachment()
            {
                return Attachment::fromPath(__DIR__.'/foo.jpg')
                    ->as('bar.jpg')
                    ->withMime('image/png');
            }
        });

        $attachment = $this->message->getSymfonyMessage()->getAttachments()[0];
        $headers = $attachment->getPreparedHeaders()->toArray();
        $this->assertSame('expected attachment body', $attachment->getBody());
        $this->assertSame('Content-Type: image/png; name=bar.jpg', $headers[0]);
        $this->assertSame('Content-Transfer-Encoding: base64', $headers[1]);
        $this->assertSame('Content-Disposition: attachment; name=bar.jpg; filename=bar.jpg', $headers[2]);

        unlink($path);
    }

    public function testItAttachesFilesViaAttachableContractFromData()
    {
        $this->message->attach(new class() implements Attachable
        {
            public function toMailAttachment()
            {
                return Attachment::fromData(fn () => 'expected attachment body', 'foo.jpg')
                    ->withMime('image/png');
            }
        });

        $attachment = $this->message->getSymfonyMessage()->getAttachments()[0];
        $headers = $attachment->getPreparedHeaders()->toArray();
        $this->assertSame('expected attachment body', $attachment->getBody());
        $this->assertSame('Content-Type: image/png; name=foo.jpg', $headers[0]);
        $this->assertSame('Content-Transfer-Encoding: base64', $headers[1]);
        $this->assertSame('Content-Disposition: attachment; name=foo.jpg; filename=foo.jpg', $headers[2]);
    }

    public function testEmbedPath()
    {
        file_put_contents($path = __DIR__.'/foo.jpg', 'bar');

        $cid = $this->message->embed($path);

        $this->assertStringStartsWith('cid:', $cid);
        $name = Str::after($cid, 'cid:');
        $attachment = $this->message->getSymfonyMessage()->getAttachments()[0];
        $headers = $attachment->getPreparedHeaders()->toArray();
        $this->assertSame('bar', $attachment->getBody());
        $this->assertSame("Content-Type: image/jpeg; name={$name}", $headers[0]);
        $this->assertSame('Content-Transfer-Encoding: base64', $headers[1]);
        $this->assertSame("Content-Disposition: inline; name={$name}; filename={$name}", $headers[2]);

        unlink($path);
    }

    public function testDataEmbed()
    {
        $cid = $this->message->embedData('bar', 'foo.jpg', 'image/png');

        $attachment = $this->message->getSymfonyMessage()->getAttachments()[0];
        $headers = $attachment->getPreparedHeaders()->toArray();
        $this->assertSame('cid:foo.jpg', $cid);
        $this->assertSame('bar', $attachment->getBody());
        $this->assertSame('Content-Type: image/png; name=foo.jpg', $headers[0]);
        $this->assertSame('Content-Transfer-Encoding: base64', $headers[1]);
        $this->assertSame('Content-Disposition: inline; name=foo.jpg; filename=foo.jpg', $headers[2]);
    }

    public function testItEmbedsFilesViaAttachableContractFromPath()
    {
        file_put_contents($path = __DIR__.'/foo.jpg', 'bar');

        $cid = $this->message->embed(new class() implements Attachable
        {
            public function toMailAttachment()
            {
                return Attachment::fromPath(__DIR__.'/foo.jpg')->as('baz')->withMime('image/png');
            }
        });

        $this->assertSame('cid:baz', $cid);
        $attachment = $this->message->getSymfonyMessage()->getAttachments()[0];
        $headers = $attachment->getPreparedHeaders()->toArray();
        $this->assertSame('bar', $attachment->getBody());
        $this->assertSame('Content-Type: image/png; name=baz', $headers[0]);
        $this->assertSame('Content-Transfer-Encoding: base64', $headers[1]);
        $this->assertSame('Content-Disposition: inline; name=baz; filename=baz', $headers[2]);

        unlink($path);
    }

    public function testItGeneratesARandomNameWhenAttachableHasNone()
    {
        file_put_contents($path = __DIR__.'/foo.jpg', 'bar');

        $cid = $this->message->embed(new class() implements Attachable
        {
            public function toMailAttachment()
            {
                return Attachment::fromPath(__DIR__.'/foo.jpg');
            }
        });

        $this->assertStringStartsWith('cid:', $cid);
        $name = Str::after($cid, 'cid:');
        $this->assertSame(16, mb_strlen($name));
        $attachment = $this->message->getSymfonyMessage()->getAttachments()[0];
        $headers = $attachment->getPreparedHeaders()->toArray();
        $this->assertSame('bar', $attachment->getBody());
        $this->assertSame("Content-Type: image/jpeg; name={$name}", $headers[0]);
        $this->assertSame('Content-Transfer-Encoding: base64', $headers[1]);
        $this->assertSame("Content-Disposition: inline; name={$name};\r\n filename={$name}", $headers[2]);

        unlink($path);
    }
}
