<?php

namespace Illuminate\Tests\Mail;

use Illuminate\Container\Container;
use Illuminate\Contracts\View\Factory;
use Illuminate\Filesystem\FilesystemManager;
use Illuminate\Foundation\Application;
use Illuminate\Mail\Mailer;
use Illuminate\Mail\SendQueuedMailable;
use Illuminate\Support\Testing\Fakes\QueueFake;
use Illuminate\Tests\App\Mail\MailableQueueableStub;
use Illuminate\Tests\App\Mail\MailableQueueableStubWithDeduplication;
use Illuminate\Tests\App\Mail\MailableQueueableStubWithDelayAttribute;
use Illuminate\Tests\App\Mail\MailableQueueableStubWithDelayQueueAndConnectionAttributes;
use Illuminate\Tests\App\Mail\MailableQueueableStubWithMessageGroup;
use Illuminate\Tests\App\Mail\MailableQueueableStubWithQueueAndConnectionAttributes;
use Laravel\SerializableClosure\SerializableClosure;
use Mockery;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Mailer\Transport\TransportInterface;

class MailableQueuedTest extends TestCase
{
    public function testQueuedMailableSent(): void
    {
        $queueFake = new QueueFake(new Application);
        $mailer = $this->getMockBuilder(Mailer::class)
            ->setConstructorArgs($this->getMocks())
            ->onlyMethods(['createMessage', 'to'])
            ->getMock();
        $mailer->setQueue($queueFake);
        $mailable = new MailableQueueableStub;
        $queueFake->assertNothingPushed();
        $mailer->send($mailable);
        $queueFake->assertPushedOn(null, SendQueuedMailable::class);
    }

    public function testQueuedMailableWithAttachmentSent(): void
    {
        $queueFake = new QueueFake(new Application);
        $mailer = $this->getMockBuilder(Mailer::class)
            ->setConstructorArgs($this->getMocks())
            ->onlyMethods(['createMessage'])
            ->getMock();
        $mailer->setQueue($queueFake);
        $mailable = new MailableQueueableStub;
        $attachmentOption = ['mime' => 'image/jpeg', 'as' => 'bar.jpg'];
        $mailable->attach('foo.jpg', $attachmentOption);
        $this->assertIsArray($mailable->attachments);
        $this->assertCount(1, $mailable->attachments);
        $this->assertEquals($mailable->attachments[0]['options'], $attachmentOption);
        $queueFake->assertNothingPushed();
        $mailer->send($mailable);
        $queueFake->assertPushedOn(null, SendQueuedMailable::class);
    }

    public function testQueuedMailableWithAttachmentFromDiskSent(): void
    {
        $app = new Application;
        $container = Container::getInstance();
        $filesystemFactory = $this->getMockBuilder(FilesystemManager::class)
            ->setConstructorArgs([$app])
            ->getMock();
        $container->instance('filesystem', $filesystemFactory);
        $queueFake = new QueueFake($app);
        $mailer = $this->getMockBuilder(Mailer::class)
            ->setConstructorArgs($this->getMocks())
            ->onlyMethods(['createMessage'])
            ->getMock();
        $mailer->setQueue($queueFake);
        $mailable = new MailableQueueableStub;
        $attachmentOption = ['mime' => 'image/jpeg', 'as' => 'bar.jpg'];

        $mailable->attachFromStorage('/', 'foo.jpg', $attachmentOption);

        $this->assertIsArray($mailable->diskAttachments);
        $this->assertCount(1, $mailable->diskAttachments);
        $this->assertEquals($mailable->diskAttachments[0]['options'], $attachmentOption);

        $queueFake->assertNothingPushed();
        $mailer->send($mailable);
        $queueFake->assertPushedOn(null, SendQueuedMailable::class);
    }

    public function testQueuedMailableForwardsMessageGroupFromMethodToQueueJob(): void
    {
        $mockedMessageGroupId = 'group-1';

        $mailable = $this->getMockBuilder(MailableQueueableStubWithMessageGroup::class)->onlyMethods(['messageGroup'])->getMock();
        $mailable->expects($this->once())->method('messageGroup')->willReturn($mockedMessageGroupId);

        $queueFake = new QueueFake(new Application);
        $mailer = $this->getMockBuilder(Mailer::class)
            ->setConstructorArgs($this->getMocks())
            ->onlyMethods(['createMessage', 'to'])
            ->getMock();
        $mailer->setQueue($queueFake);
        $queueFake->assertNothingPushed();
        $mailer->send($mailable);
        $queueFake->assertPushedOn(null, SendQueuedMailable::class);

        $pushedJob = $queueFake->pushed(SendQueuedMailable::class)->first();
        $this->assertEquals($mockedMessageGroupId, $pushedJob->messageGroup);
    }

    public function testQueuedMailableForwardsMessageGroupFromPropertyOverridingMethodToQueueJob(): void
    {
        $mockedMessageGroupId = 'group-1';

        // Ensure the messageGroup method is not called when a messageGroup property is provided.
        $mailable = $this->getMockBuilder(MailableQueueableStubWithMessageGroup::class)->onlyMethods(['messageGroup'])->getMock();
        $mailable->expects($this->never())->method('messageGroup')->willReturn('this-should-not-be-used');
        $mailable->onGroup($mockedMessageGroupId);

        $queueFake = new QueueFake(new Application);
        $mailer = $this->getMockBuilder(Mailer::class)
            ->setConstructorArgs($this->getMocks())
            ->onlyMethods(['createMessage', 'to'])
            ->getMock();
        $mailer->setQueue($queueFake);
        $queueFake->assertNothingPushed();
        $mailer->send($mailable);
        $queueFake->assertPushedOn(null, SendQueuedMailable::class);

        $pushedJob = $queueFake->pushed(SendQueuedMailable::class)->first();
        $this->assertEquals($mockedMessageGroupId, $pushedJob->messageGroup);
    }

    public function testQueuedMailableForwardsDeduplicatorToQueueJob(): void
    {
        $mockedDeduplicator = fn ($payload, $queue) => 'deduplication-id-1';

        $queueFake = new QueueFake(new Application);
        $mailer = $this->getMockBuilder(Mailer::class)
            ->setConstructorArgs($this->getMocks())
            ->onlyMethods(['createMessage', 'to'])
            ->getMock();
        $mailer->setQueue($queueFake);
        $mailable = (new MailableQueueableStub)->withDeduplicator($mockedDeduplicator);
        $queueFake->assertNothingPushed();
        $mailer->send($mailable);
        $queueFake->assertPushedOn(null, SendQueuedMailable::class);

        $pushedJob = $queueFake->pushed(SendQueuedMailable::class)->first();
        $this->assertInstanceOf(SerializableClosure::class, $pushedJob->deduplicator);
        $this->assertEquals($mockedDeduplicator, $pushedJob->deduplicator->getClosure());
    }

    public function testQueuedMailableRespectsDelayAttribute(): void
    {
        $queueFake = new QueueFake(new Application);
        $mailer = $this->getMockBuilder(Mailer::class)
            ->setConstructorArgs($this->getMocks())
            ->onlyMethods(['createMessage', 'to'])
            ->getMock();
        $mailer->setQueue($queueFake);
        $mailable = new MailableQueueableStubWithDelayAttribute;
        $queueFake->assertNothingPushed();
        $mailer->send($mailable);
        $queueFake->assertPushedOn(null, SendQueuedMailable::class);

        $pushedJob = $queueFake->pushed(SendQueuedMailable::class)->first();
        $this->assertEquals(30, $pushedJob->delay);
    }

    public function testQueuedMailableDelayPropertyOverridesAttribute(): void
    {
        $queueFake = new QueueFake(new Application);
        $mailer = $this->getMockBuilder(Mailer::class)
            ->setConstructorArgs($this->getMocks())
            ->onlyMethods(['createMessage', 'to'])
            ->getMock();
        $mailer->setQueue($queueFake);
        $mailable = new MailableQueueableStubWithDelayAttribute;
        $mailable->delay = 60;
        $queueFake->assertNothingPushed();
        $mailer->send($mailable);
        $queueFake->assertPushedOn(null, SendQueuedMailable::class);

        $pushedJob = $queueFake->pushed(SendQueuedMailable::class)->first();
        $this->assertEquals(60, $pushedJob->delay);
    }

    public function testQueuedMailableRespectsQueueAndConnectionAttributes(): void
    {
        $queueFake = new MailableQueueFake(new Application);
        $mailer = $this->getMockBuilder(Mailer::class)
            ->setConstructorArgs($this->getMocks())
            ->onlyMethods(['createMessage', 'to'])
            ->getMock();
        $mailer->setQueue($queueFake);
        $mailable = new MailableQueueableStubWithQueueAndConnectionAttributes;
        $queueFake->assertNothingPushed();
        $mailer->send($mailable);
        $queueFake->assertPushedOn('mail-queue', SendQueuedMailable::class);

        $pushedJob = $queueFake->pushed(SendQueuedMailable::class)->first();
        $this->assertSame('redis', $queueFake->connectionName);
        $this->assertSame('mail-queue', $pushedJob->queue);
        $this->assertSame('redis', $pushedJob->connection);
    }

    public function testDelayedQueuedMailableRespectsQueueAndConnectionAttributes(): void
    {
        $queueFake = new MailableQueueFake(new Application);
        $mailer = $this->getMockBuilder(Mailer::class)
            ->setConstructorArgs($this->getMocks())
            ->onlyMethods(['createMessage', 'to'])
            ->getMock();
        $mailer->setQueue($queueFake);
        $mailable = new MailableQueueableStubWithDelayQueueAndConnectionAttributes;
        $queueFake->assertNothingPushed();
        $mailer->send($mailable);
        $queueFake->assertPushedOn('delayed-mail-queue', SendQueuedMailable::class);

        $pushedJob = $queueFake->pushed(SendQueuedMailable::class)->first();
        $this->assertSame('sqs', $queueFake->connectionName);
        $this->assertSame('delayed-mail-queue', $pushedJob->queue);
        $this->assertSame('sqs', $pushedJob->connection);
        $this->assertEquals(30, $pushedJob->delay);
    }

    public function testQueuedMailableForwardsDeduplicationIdMethodToQueueJob(): void
    {
        $queueFake = new QueueFake(new Application);
        $mailer = $this->getMockBuilder(Mailer::class)
            ->setConstructorArgs($this->getMocks())
            ->onlyMethods(['createMessage', 'to'])
            ->getMock();
        $mailer->setQueue($queueFake);
        $mailable = new MailableQueueableStubWithDeduplication;
        $queueFake->assertNothingPushed();
        $mailer->send($mailable);
        $queueFake->assertPushedOn(null, SendQueuedMailable::class);

        $pushedJob = $queueFake->pushed(SendQueuedMailable::class)->first();
        $this->assertInstanceOf(SerializableClosure::class, $pushedJob->deduplicator);
        $this->assertEquals($mailable->deduplicationId(...), $pushedJob->deduplicator->getClosure());
    }

    public function testQueueSetsBackedEnumQueueOnMailable(): void
    {
        $queueFake = new QueueFake(new Application);
        $mailer = new Mailer(...$this->getMocks());
        $mailer->setQueue($queueFake);

        $mailer->queue(new MailableQueueableStub, MailableQueue::Emails);

        $queueFake->assertPushedOn('emails', SendQueuedMailable::class);
    }

    public function testLaterSetsQueueOnMailable(): void
    {
        $queueFake = new QueueFake(new Application);
        $mailer = $this->getMockBuilder(Mailer::class)
            ->setConstructorArgs($this->getMocks())
            ->onlyMethods(['createMessage', 'to'])
            ->getMock();
        $mailer->setQueue($queueFake);

        $mailable = new MailableQueueableStub;
        $mailer->later(60, $mailable, 'emails');

        $queueFake->assertPushed(SendQueuedMailable::class, function ($job) {
            return $job->queue === 'emails';
        });
    }

    public function testLaterWithoutQueueUsesDefault(): void
    {
        $queueFake = new QueueFake(new Application);
        $mailer = $this->getMockBuilder(Mailer::class)
            ->setConstructorArgs($this->getMocks())
            ->onlyMethods(['createMessage', 'to'])
            ->getMock();
        $mailer->setQueue($queueFake);

        $mailable = new MailableQueueableStub;
        $mailer->later(60, $mailable);

        $queueFake->assertPushed(SendQueuedMailable::class, function ($job) {
            return $job->queue === null;
        });
    }

    protected function getMocks()
    {
        return ['smtp', Mockery::mock(Factory::class), Mockery::mock(TransportInterface::class)];
    }
}

enum MailableQueue: string
{
    case Emails = 'emails';
}

class MailableQueueFake extends QueueFake
{
    public $connectionName;

    public function connection($value = null)
    {
        $this->connectionName = $value;

        return parent::connection($value);
    }
}
