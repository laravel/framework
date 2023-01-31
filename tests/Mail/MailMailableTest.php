<?php

namespace Illuminate\Tests\Mail;

use Illuminate\Container\Container;
use Illuminate\Contracts\Mail\Attachable;
use Illuminate\Contracts\View\Factory;
use Illuminate\Mail\Attachment;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailer;
use Illuminate\Mail\Transport\ArrayTransport;
use Mockery as m;
use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\TestCase;

class MailMailableTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
    }

    public function testMailableSetsRecipientsCorrectly()
    {
        $mailable = new WelcomeMailableStub;
        $mailable->to('taylor@laravel.com');
        $this->assertEquals([['name' => null, 'address' => 'taylor@laravel.com']], $mailable->to);
        $this->assertTrue($mailable->hasTo('taylor@laravel.com'));
        $mailable->assertHasTo('taylor@laravel.com');
        $mailable->to('taylor@laravel.com', 'Taylor Otwell');

        // Add the same recipient again, but with a different name. This should set the name correctly.
        $this->assertTrue($mailable->hasTo('taylor@laravel.com', 'Taylor Otwell'));
        $mailable->assertHasTo('taylor@laravel.com', 'Taylor Otwell');

        $mailable = new WelcomeMailableStub;
        $mailable->to('taylor@laravel.com', 'Taylor Otwell');
        $this->assertEquals([['name' => 'Taylor Otwell', 'address' => 'taylor@laravel.com']], $mailable->to);
        $this->assertTrue($mailable->hasTo('taylor@laravel.com', 'Taylor Otwell'));
        $this->assertTrue($mailable->hasTo('taylor@laravel.com'));
        $mailable->assertHasTo('taylor@laravel.com', 'Taylor Otwell');
        $mailable->assertHasTo('taylor@laravel.com');

        $mailable = new WelcomeMailableStub;
        $mailable->to(['taylor@laravel.com']);
        $this->assertEquals([['name' => null, 'address' => 'taylor@laravel.com']], $mailable->to);
        $this->assertTrue($mailable->hasTo('taylor@laravel.com'));
        $this->assertFalse($mailable->hasTo('taylor@laravel.com', 'Taylor Otwell'));
        $mailable->assertHasTo('taylor@laravel.com');
        try {
            $mailable->assertHasTo('taylor@laravel.com', 'Taylor Otwell');
            $this->fail();
        } catch (AssertionFailedError $e) {
            $this->assertSame("Did not see expected recipient [taylor@laravel.com (Taylor Otwell)] in email recipients.\nFailed asserting that false is true.", $e->getMessage());
        }

        $mailable = new WelcomeMailableStub;
        $mailable->to([['name' => 'Taylor Otwell', 'email' => 'taylor@laravel.com']]);
        $this->assertEquals([['name' => 'Taylor Otwell', 'address' => 'taylor@laravel.com']], $mailable->to);
        $this->assertTrue($mailable->hasTo('taylor@laravel.com', 'Taylor Otwell'));
        $this->assertTrue($mailable->hasTo('taylor@laravel.com'));
        $mailable->assertHasTo('taylor@laravel.com');

        $mailable = new WelcomeMailableStub;
        $mailable->to(new MailableTestUserStub);
        $this->assertEquals([['name' => 'Taylor Otwell', 'address' => 'taylor@laravel.com']], $mailable->to);
        $this->assertTrue($mailable->hasTo(new MailableTestUserStub));
        $this->assertTrue($mailable->hasTo('taylor@laravel.com'));
        $mailable->assertHasTo('taylor@laravel.com');

        $mailable = new WelcomeMailableStub;
        $mailable->to(collect([new MailableTestUserStub]));
        $this->assertEquals([['name' => 'Taylor Otwell', 'address' => 'taylor@laravel.com']], $mailable->to);
        $this->assertTrue($mailable->hasTo(new MailableTestUserStub));
        $this->assertTrue($mailable->hasTo('taylor@laravel.com'));
        $mailable->assertHasTo('taylor@laravel.com');

        $mailable = new WelcomeMailableStub;
        $mailable->to(collect([new MailableTestUserStub, new MailableTestUserStub, new MailableTestUserStub2]));
        $this->assertEquals([
            ['name' => 'Taylor Otwell', 'address' => 'taylor@laravel.com'],
            ['name' => 'Laravel Framework', 'address' => 'contact@laravel.com'],
        ], $mailable->to);
        $this->assertTrue($mailable->hasTo(new MailableTestUserStub));
        $this->assertTrue($mailable->hasTo('taylor@laravel.com'));
        $mailable->assertHasTo('taylor@laravel.com');

        foreach (['', null, [], false] as $address) {
            $mailable = new WelcomeMailableStub;
            $mailable->to($address);
            $this->assertFalse($mailable->hasTo(new MailableTestUserStub));
            $this->assertFalse($mailable->hasTo($address));
            try {
                $mailable->assertHasTo($address);
                $this->fail();
            } catch (AssertionFailedError $e) {
                if (! is_string($address)) {
                    $address = json_encode($address);
                }
                $this->assertSame("Did not see expected recipient [{$address}] in email recipients.\nFailed asserting that false is true.", $e->getMessage());
            }
        }
    }

    public function testMailableSetsCcRecipientsCorrectly()
    {
        $mailable = new WelcomeMailableStub;
        $mailable->cc('taylor@laravel.com');
        $this->assertEquals([['name' => null, 'address' => 'taylor@laravel.com']], $mailable->cc);
        $this->assertTrue($mailable->hasCc('taylor@laravel.com'));
        $mailable->assertHasCc('taylor@laravel.com');

        $mailable = new WelcomeMailableStub;
        $mailable->cc('taylor@laravel.com', 'Taylor Otwell');
        $this->assertEquals([['name' => 'Taylor Otwell', 'address' => 'taylor@laravel.com']], $mailable->cc);
        $this->assertTrue($mailable->hasCc('taylor@laravel.com', 'Taylor Otwell'));
        $this->assertTrue($mailable->hasCc('taylor@laravel.com'));
        $mailable->assertHasCc('taylor@laravel.com', 'Taylor Otwell');
        $mailable->assertHasCc('taylor@laravel.com');

        $mailable = new WelcomeMailableStub;
        $mailable->cc(['taylor@laravel.com']);
        $this->assertEquals([['name' => null, 'address' => 'taylor@laravel.com']], $mailable->cc);
        $this->assertTrue($mailable->hasCc('taylor@laravel.com'));
        $this->assertFalse($mailable->hasCc('taylor@laravel.com', 'Taylor Otwell'));
        $mailable->assertHasCc('taylor@laravel.com');
        try {
            $mailable->assertHasCc('taylor@laravel.com', 'Taylor Otwell');
            $this->fail();
        } catch (AssertionFailedError $e) {
            $this->assertSame("Did not see expected recipient [taylor@laravel.com (Taylor Otwell)] in email recipients.\nFailed asserting that false is true.", $e->getMessage());
        }

        $mailable = new WelcomeMailableStub;
        $mailable->cc([['name' => 'Taylor Otwell', 'email' => 'taylor@laravel.com']]);
        $this->assertEquals([['name' => 'Taylor Otwell', 'address' => 'taylor@laravel.com']], $mailable->cc);
        $this->assertTrue($mailable->hasCc('taylor@laravel.com', 'Taylor Otwell'));
        $this->assertTrue($mailable->hasCc('taylor@laravel.com'));
        $mailable->assertHasCc('taylor@laravel.com', 'Taylor Otwell');
        $mailable->assertHasCc('taylor@laravel.com');

        $mailable = new WelcomeMailableStub;
        $mailable->cc(new MailableTestUserStub);
        $this->assertEquals([['name' => 'Taylor Otwell', 'address' => 'taylor@laravel.com']], $mailable->cc);
        $this->assertTrue($mailable->hasCc(new MailableTestUserStub));
        $this->assertTrue($mailable->hasCc('taylor@laravel.com'));
        $mailable->assertHasCc('taylor@laravel.com');

        $mailable = new WelcomeMailableStub;
        $mailable->cc(collect([new MailableTestUserStub]));
        $this->assertEquals([['name' => 'Taylor Otwell', 'address' => 'taylor@laravel.com']], $mailable->cc);
        $this->assertTrue($mailable->hasCc(new MailableTestUserStub));
        $this->assertTrue($mailable->hasCc('taylor@laravel.com'));
        $mailable->assertHasCc('taylor@laravel.com');

        $mailable = new WelcomeMailableStub;
        $mailable->cc(collect([new MailableTestUserStub, new MailableTestUserStub, new MailableTestUserStub2]));
        $this->assertEquals([
            ['name' => 'Taylor Otwell', 'address' => 'taylor@laravel.com'],
            ['name' => 'Laravel Framework', 'address' => 'contact@laravel.com'],
        ], $mailable->cc);
        $this->assertTrue($mailable->hasCc(new MailableTestUserStub));
        $this->assertTrue($mailable->hasCc('taylor@laravel.com'));
        $mailable->assertHasCc('taylor@laravel.com');

        $mailable = new WelcomeMailableStub;
        $mailable->cc(['taylor@laravel.com', 'not-taylor@laravel.com']);
        $this->assertEquals([
            ['name' => null, 'address' => 'taylor@laravel.com'],
            ['name' => null, 'address' => 'not-taylor@laravel.com'],
        ], $mailable->cc);
        $this->assertTrue($mailable->hasCc('taylor@laravel.com'));
        $this->assertTrue($mailable->hasCc('not-taylor@laravel.com'));
        $mailable->assertHasCc('taylor@laravel.com');
        $mailable->assertHasCc('not-taylor@laravel.com');

        foreach (['', null, [], false] as $address) {
            $mailable = new WelcomeMailableStub;
            $mailable->cc($address);
            $this->assertFalse($mailable->hasCc(new MailableTestUserStub));
            $this->assertFalse($mailable->hasCc($address));
            try {
                $mailable->assertHasCc($address);
                $this->fail();
            } catch (AssertionFailedError $e) {
                if (! is_string($address)) {
                    $address = json_encode($address);
                }
                $this->assertSame("Did not see expected recipient [{$address}] in email recipients.\nFailed asserting that false is true.", $e->getMessage());
            }
        }
    }

    public function testMailableSetsBccRecipientsCorrectly()
    {
        $mailable = new WelcomeMailableStub;
        $mailable->bcc('taylor@laravel.com');
        $this->assertEquals([['name' => null, 'address' => 'taylor@laravel.com']], $mailable->bcc);
        $this->assertTrue($mailable->hasBcc('taylor@laravel.com'));
        $mailable->assertHasBcc('taylor@laravel.com');

        $mailable = new WelcomeMailableStub;
        $mailable->bcc('taylor@laravel.com', 'Taylor Otwell');
        $this->assertEquals([['name' => 'Taylor Otwell', 'address' => 'taylor@laravel.com']], $mailable->bcc);
        $this->assertTrue($mailable->hasBcc('taylor@laravel.com', 'Taylor Otwell'));
        $this->assertTrue($mailable->hasBcc('taylor@laravel.com'));
        $mailable->assertHasBcc('taylor@laravel.com', 'Taylor Otwell');
        $mailable->assertHasBcc('taylor@laravel.com');

        $mailable = new WelcomeMailableStub;
        $mailable->bcc(['taylor@laravel.com']);
        $this->assertEquals([['name' => null, 'address' => 'taylor@laravel.com']], $mailable->bcc);
        $this->assertTrue($mailable->hasBcc('taylor@laravel.com'));
        $this->assertFalse($mailable->hasBcc('taylor@laravel.com', 'Taylor Otwell'));
        $mailable->assertHasBcc('taylor@laravel.com');
        try {
            $mailable->assertHasBcc('taylor@laravel.com', 'Taylor Otwell');
            $this->fail();
        } catch (AssertionFailedError $e) {
            $this->assertSame("Did not see expected recipient [taylor@laravel.com (Taylor Otwell)] in email recipients.\nFailed asserting that false is true.", $e->getMessage());
        }

        $mailable = new WelcomeMailableStub;
        $mailable->bcc([['name' => 'Taylor Otwell', 'email' => 'taylor@laravel.com']]);
        $this->assertEquals([['name' => 'Taylor Otwell', 'address' => 'taylor@laravel.com']], $mailable->bcc);
        $this->assertTrue($mailable->hasBcc('taylor@laravel.com', 'Taylor Otwell'));
        $this->assertTrue($mailable->hasBcc('taylor@laravel.com'));
        $mailable->assertHasBcc('taylor@laravel.com', 'Taylor Otwell');
        $mailable->assertHasBcc('taylor@laravel.com');

        $mailable = new WelcomeMailableStub;
        $mailable->bcc(new MailableTestUserStub);
        $this->assertEquals([['name' => 'Taylor Otwell', 'address' => 'taylor@laravel.com']], $mailable->bcc);
        $this->assertTrue($mailable->hasBcc(new MailableTestUserStub));
        $this->assertTrue($mailable->hasBcc('taylor@laravel.com'));
        $mailable->assertHasBcc('taylor@laravel.com');

        $mailable = new WelcomeMailableStub;
        $mailable->bcc(collect([new MailableTestUserStub]));
        $this->assertEquals([['name' => 'Taylor Otwell', 'address' => 'taylor@laravel.com']], $mailable->bcc);
        $this->assertTrue($mailable->hasBcc(new MailableTestUserStub));
        $this->assertTrue($mailable->hasBcc('taylor@laravel.com'));
        $mailable->assertHasBcc('taylor@laravel.com');

        $mailable = new WelcomeMailableStub;
        $mailable->bcc(collect([new MailableTestUserStub, new MailableTestUserStub, new MailableTestUserStub2]));
        $this->assertEquals([
            ['name' => 'Taylor Otwell', 'address' => 'taylor@laravel.com'],
            ['name' => 'Laravel Framework', 'address' => 'contact@laravel.com'],
        ], $mailable->bcc);
        $this->assertTrue($mailable->hasBcc(new MailableTestUserStub));
        $this->assertTrue($mailable->hasBcc('taylor@laravel.com'));
        $mailable->assertHasBcc('taylor@laravel.com');

        $mailable = new WelcomeMailableStub;
        $mailable->bcc(['taylor@laravel.com', 'not-taylor@laravel.com']);
        $this->assertEquals([
            ['name' => null, 'address' => 'taylor@laravel.com'],
            ['name' => null, 'address' => 'not-taylor@laravel.com'],
        ], $mailable->bcc);
        $this->assertTrue($mailable->hasBcc('taylor@laravel.com'));
        $this->assertTrue($mailable->hasBcc('not-taylor@laravel.com'));
        $mailable->assertHasBcc('taylor@laravel.com');
        $mailable->assertHasBcc('not-taylor@laravel.com');

        foreach (['', null, [], false] as $address) {
            $mailable = new WelcomeMailableStub;
            $mailable->bcc($address);
            $this->assertFalse($mailable->hasBcc(new MailableTestUserStub));
            $this->assertFalse($mailable->hasBcc($address));
            try {
                $mailable->assertHasBcc($address);
                $this->fail();
            } catch (AssertionFailedError $e) {
                if (! is_string($address)) {
                    $address = json_encode($address);
                }
                $this->assertSame("Did not see expected recipient [{$address}] in email recipients.\nFailed asserting that false is true.", $e->getMessage());
            }
        }
    }

    public function testMailableSetsReplyToCorrectly()
    {
        $mailable = new WelcomeMailableStub;
        $mailable->replyTo('taylor@laravel.com');
        $this->assertEquals([['name' => null, 'address' => 'taylor@laravel.com']], $mailable->replyTo);
        $this->assertTrue($mailable->hasReplyTo('taylor@laravel.com'));
        $mailable->assertHasReplyTo('taylor@laravel.com');

        $mailable = new WelcomeMailableStub;
        $mailable->replyTo('taylor@laravel.com', 'Taylor Otwell');
        $this->assertEquals([['name' => 'Taylor Otwell', 'address' => 'taylor@laravel.com']], $mailable->replyTo);
        $this->assertTrue($mailable->hasReplyTo('taylor@laravel.com', 'Taylor Otwell'));
        $this->assertTrue($mailable->hasReplyTo('taylor@laravel.com'));
        $mailable->assertHasReplyTo('taylor@laravel.com', 'Taylor Otwell');
        $mailable->assertHasReplyTo('taylor@laravel.com');

        $mailable = new WelcomeMailableStub;
        $mailable->replyTo(['taylor@laravel.com']);
        $this->assertEquals([['name' => null, 'address' => 'taylor@laravel.com']], $mailable->replyTo);
        $this->assertTrue($mailable->hasReplyTo('taylor@laravel.com'));
        $this->assertFalse($mailable->hasReplyTo('taylor@laravel.com', 'Taylor Otwell'));
        $mailable->assertHasReplyTo('taylor@laravel.com');
        try {
            $mailable->assertHasReplyTo('taylor@laravel.com', 'Taylor Otwell');
            $this->fail();
        } catch (AssertionFailedError $e) {
            $this->assertSame("Did not see expected address [taylor@laravel.com (Taylor Otwell)] as email 'reply to' recipient.\nFailed asserting that false is true.", $e->getMessage());
        }

        $mailable = new WelcomeMailableStub;
        $mailable->replyTo([['name' => 'Taylor Otwell', 'email' => 'taylor@laravel.com']]);
        $this->assertEquals([['name' => 'Taylor Otwell', 'address' => 'taylor@laravel.com']], $mailable->replyTo);
        $this->assertTrue($mailable->hasReplyTo('taylor@laravel.com', 'Taylor Otwell'));
        $this->assertTrue($mailable->hasReplyTo('taylor@laravel.com'));
        $mailable->assertHasReplyTo('taylor@laravel.com');
        $mailable->assertHasReplyTo('taylor@laravel.com', 'Taylor Otwell');

        $mailable = new WelcomeMailableStub;
        $mailable->replyTo(new MailableTestUserStub);
        $this->assertEquals([['name' => 'Taylor Otwell', 'address' => 'taylor@laravel.com']], $mailable->replyTo);
        $this->assertTrue($mailable->hasReplyTo(new MailableTestUserStub));
        $this->assertTrue($mailable->hasReplyTo('taylor@laravel.com'));
        $mailable->assertHasReplyTo('taylor@laravel.com');

        $mailable = new WelcomeMailableStub;
        $mailable->replyTo(collect([new MailableTestUserStub]));
        $this->assertEquals([['name' => 'Taylor Otwell', 'address' => 'taylor@laravel.com']], $mailable->replyTo);
        $this->assertTrue($mailable->hasReplyTo(new MailableTestUserStub));
        $this->assertTrue($mailable->hasReplyTo('taylor@laravel.com'));
        $mailable->assertHasReplyTo('taylor@laravel.com');

        $mailable = new WelcomeMailableStub;
        $mailable->replyTo(collect([new MailableTestUserStub, new MailableTestUserStub, new MailableTestUserStub2]));
        $this->assertEquals([
            ['name' => 'Taylor Otwell', 'address' => 'taylor@laravel.com'],
            ['name' => 'Laravel Framework', 'address' => 'contact@laravel.com'],
        ], $mailable->replyTo);
        $this->assertTrue($mailable->hasReplyTo(new MailableTestUserStub));
        $this->assertTrue($mailable->hasReplyTo('taylor@laravel.com'));
        $mailable->assertHasReplyTo('taylor@laravel.com');

        foreach (['', null, [], false] as $address) {
            $mailable = new WelcomeMailableStub;
            $mailable->replyTo($address);
            $this->assertFalse($mailable->hasReplyTo(new MailableTestUserStub));
            $this->assertFalse($mailable->hasReplyTo($address));
            try {
                $mailable->assertHasReplyTo($address);
                $this->fail();
            } catch (AssertionFailedError $e) {
                if (! is_string($address)) {
                    $address = json_encode($address);
                }
                $this->assertSame("Did not see expected address [{$address}] as email 'reply to' recipient.\nFailed asserting that false is true.", $e->getMessage());
            }
        }
    }

    public function testMailableSetsFromCorrectly()
    {
        $mailable = new WelcomeMailableStub;
        $mailable->from('taylor@laravel.com');
        $this->assertEquals([['name' => null, 'address' => 'taylor@laravel.com']], $mailable->from);
        $this->assertTrue($mailable->hasFrom('taylor@laravel.com'));
        $mailable->assertFrom('taylor@laravel.com');

        $mailable = new WelcomeMailableStub;
        $mailable->from('taylor@laravel.com', 'Taylor Otwell');
        $this->assertEquals([['name' => 'Taylor Otwell', 'address' => 'taylor@laravel.com']], $mailable->from);
        $this->assertTrue($mailable->hasFrom('taylor@laravel.com', 'Taylor Otwell'));
        $this->assertTrue($mailable->hasFrom('taylor@laravel.com'));
        $mailable->assertFrom('taylor@laravel.com', 'Taylor Otwell');
        $mailable->assertFrom('taylor@laravel.com');

        $mailable = new WelcomeMailableStub;
        $mailable->from(['taylor@laravel.com']);
        $this->assertEquals([['name' => null, 'address' => 'taylor@laravel.com']], $mailable->from);
        $this->assertTrue($mailable->hasFrom('taylor@laravel.com'));
        $this->assertFalse($mailable->hasFrom('taylor@laravel.com', 'Taylor Otwell'));
        $mailable->assertFrom('taylor@laravel.com');
        try {
            $mailable->assertFrom('taylor@laravel.com', 'Taylor Otwell');
            $this->fail();
        } catch (AssertionFailedError $e) {
            $this->assertSame("Email was not from expected address [taylor@laravel.com (Taylor Otwell)].\nFailed asserting that false is true.", $e->getMessage());
        }

        $mailable = new WelcomeMailableStub;
        $mailable->from([['name' => 'Taylor Otwell', 'email' => 'taylor@laravel.com']]);
        $this->assertEquals([['name' => 'Taylor Otwell', 'address' => 'taylor@laravel.com']], $mailable->from);
        $this->assertTrue($mailable->hasFrom('taylor@laravel.com', 'Taylor Otwell'));
        $this->assertTrue($mailable->hasFrom('taylor@laravel.com'));
        $mailable->assertFrom('taylor@laravel.com', 'Taylor Otwell');
        $mailable->assertFrom('taylor@laravel.com');

        $mailable = new WelcomeMailableStub;
        $mailable->from(new MailableTestUserStub);
        $this->assertEquals([['name' => 'Taylor Otwell', 'address' => 'taylor@laravel.com']], $mailable->from);
        $this->assertTrue($mailable->hasFrom(new MailableTestUserStub));
        $this->assertTrue($mailable->hasFrom('taylor@laravel.com'));
        $mailable->assertFrom('taylor@laravel.com');

        $mailable = new WelcomeMailableStub;
        $mailable->from(collect([new MailableTestUserStub]));
        $this->assertEquals([['name' => 'Taylor Otwell', 'address' => 'taylor@laravel.com']], $mailable->from);
        $this->assertTrue($mailable->hasFrom(new MailableTestUserStub));
        $this->assertTrue($mailable->hasFrom('taylor@laravel.com'));
        $mailable->assertFrom('taylor@laravel.com');

        $mailable = new WelcomeMailableStub;
        $mailable->from(collect([new MailableTestUserStub, new MailableTestUserStub, new MailableTestUserStub2]));
        $this->assertEquals([
            ['name' => 'Taylor Otwell', 'address' => 'taylor@laravel.com'],
            ['name' => 'Laravel Framework', 'address' => 'contact@laravel.com'],
        ], $mailable->from);
        $this->assertTrue($mailable->hasFrom(new MailableTestUserStub));
        $this->assertTrue($mailable->hasFrom('taylor@laravel.com'));
        $mailable->assertFrom('taylor@laravel.com');

        foreach (['', null, [], false] as $address) {
            $mailable = new WelcomeMailableStub;
            $mailable->from($address);
            $this->assertFalse($mailable->hasFrom(new MailableTestUserStub));
            $this->assertFalse($mailable->hasFrom($address));
            try {
                $mailable->assertFrom($address);
                $this->fail();
            } catch (AssertionFailedError $e) {
                if (! is_string($address)) {
                    $address = json_encode($address);
                }
                $this->assertSame("Email was not from expected address [{$address}].\nFailed asserting that false is true.", $e->getMessage());
            }
        }
    }

    public function testMailableSetsSubjectCorrectly()
    {
        $mailable = new WelcomeMailableStub;
        $mailable->subject('foo');
        $this->assertTrue($mailable->hasSubject('foo'));
    }

    public function testItIgnoresDuplicatedRawAttachments()
    {
        $mailable = new WelcomeMailableStub;

        $mailable->attachData('content1', 'report-1.txt');
        $this->assertCount(1, $mailable->rawAttachments);

        $mailable->attachData('content2', 'report-2.txt');
        $this->assertCount(2, $mailable->rawAttachments);

        $mailable->attachData('content1', 'report-1.txt');
        $mailable->attachData('content2', 'report-2.txt');
        $this->assertCount(2, $mailable->rawAttachments);

        $mailable->attachData('content1', 'report-3.txt');
        $mailable->attachData('content2', 'report-4.txt');
        $this->assertCount(4, $mailable->rawAttachments);

        $this->assertSame([
            [
                'data' => 'content1',
                'name' => 'report-1.txt',
                'options' => [],
            ],
            [
                'data' => 'content2',
                'name' => 'report-2.txt',
                'options' => [],
            ],
            [
                'data' => 'content1',
                'name' => 'report-3.txt',
                'options' => [],
            ],
            [
                'data' => 'content2',
                'name' => 'report-4.txt',
                'options' => [],
            ],
        ], $mailable->rawAttachments);
    }

    public function testItIgnoresDuplicateStorageAttachments()
    {
        $mailable = new WelcomeMailableStub;

        $mailable->attachFromStorageDisk('disk1', 'sample/file.txt');
        $this->assertCount(1, $mailable->diskAttachments);

        $mailable->attachFromStorageDisk('disk1', 'sample/file2.txt');
        $this->assertCount(2, $mailable->diskAttachments);

        $mailable->attachFromStorageDisk('disk1', 'sample/file.txt', 'file.txt');
        $mailable->attachFromStorageDisk('disk1', 'sample/file2.txt');
        $this->assertCount(2, $mailable->diskAttachments);

        $mailable->attachFromStorageDisk('disk2', 'sample/file.txt', 'file.txt');
        $mailable->attachFromStorageDisk('disk2', 'sample/file2.txt');
        $this->assertCount(4, $mailable->diskAttachments);

        $mailable->attachFromStorageDisk('disk1', 'sample/file.txt', 'custom.txt');
        $this->assertCount(5, $mailable->diskAttachments);

        $this->assertSame([
            [
                'disk' => 'disk1',
                'path' => 'sample/file.txt',
                'name' => 'file.txt',
                'options' => [],
            ],
            [
                'disk' => 'disk1',
                'path' => 'sample/file2.txt',
                'name' => 'file2.txt',
                'options' => [],
            ],
            [
                'disk' => 'disk2',
                'path' => 'sample/file.txt',
                'name' => 'file.txt',
                'options' => [],
            ],
            [
                'disk' => 'disk2',
                'path' => 'sample/file2.txt',
                'name' => 'file2.txt',
                'options' => [],
            ],
            [
                'disk' => 'disk1',
                'path' => 'sample/file.txt',
                'name' => 'custom.txt',
                'options' => [],
            ],
        ], $mailable->diskAttachments);
    }

    public function testMailableBuildsViewData()
    {
        $mailable = new WelcomeMailableStub;

        $mailable->build();

        $expected = [
            'first_name' => 'Taylor',
            'lastName' => 'Otwell',
            'framework' => 'Laravel',
        ];

        $this->assertSame($expected, $mailable->buildViewData());
    }

    public function testMailerMayBeSet()
    {
        $mailable = new WelcomeMailableStub;

        $mailable->mailer('array');

        $mailable = unserialize(serialize($mailable));

        $this->assertSame('array', $mailable->mailer);
    }

    public function testMailablePriorityGetsSent()
    {
        $view = m::mock(Factory::class);

        $mailer = new Mailer('array', $view, new ArrayTransport);

        $mailable = new WelcomeMailableStub;
        $mailable->to('hello@laravel.com');
        $mailable->from('taylor@laravel.com');
        $mailable->html('test content');

        $mailable->priority(1);

        $sentMessage = $mailer->send($mailable);

        $this->assertSame('hello@laravel.com', $sentMessage->getEnvelope()->getRecipients()[0]->getAddress());
        $this->assertStringContainsString('X-Priority: 1 (Highest)', $sentMessage->toString());
    }

    public function testMailableMetadataGetsSent()
    {
        $view = m::mock(Factory::class);

        $mailer = new Mailer('array', $view, new ArrayTransport);

        $mailable = new WelcomeMailableStub;
        $mailable->to('hello@laravel.com');
        $mailable->from('taylor@laravel.com');
        $mailable->html('test content');

        $mailable->metadata('origin', 'test-suite');
        $mailable->metadata('user_id', 1);

        $sentMessage = $mailer->send($mailable);

        $this->assertSame('hello@laravel.com', $sentMessage->getEnvelope()->getRecipients()[0]->getAddress());
        $this->assertStringContainsString('X-Metadata-origin: test-suite', $sentMessage->toString());
        $this->assertStringContainsString('X-Metadata-user_id: 1', $sentMessage->toString());

        $this->assertTrue($mailable->hasMetadata('origin', 'test-suite'));
        $this->assertTrue($mailable->hasMetadata('user_id', 1));
        $this->assertFalse($mailable->hasMetadata('test', 'test'));
        $mailable->assertHasMetadata('origin', 'test-suite');
        $mailable->assertHasMetadata('user_id', 1);
        try {
            $mailable->assertHasMetadata('test', 'test');
            $this->fail();
        } catch (AssertionFailedError $e) {
            $this->assertSame("Did not see expected key [test] and value [test] in email metadata.\nFailed asserting that false is true.", $e->getMessage());
        }
    }

    public function testMailableTagGetsSent()
    {
        $view = m::mock(Factory::class);

        $mailer = new Mailer('array', $view, new ArrayTransport);

        $mailable = new WelcomeMailableStub;
        $mailable->to('hello@laravel.com');
        $mailable->from('taylor@laravel.com');
        $mailable->html('test content');

        $mailable->tag('test');
        $mailable->tag('foo');

        $sentMessage = $mailer->send($mailable);

        $this->assertSame('hello@laravel.com', $sentMessage->getEnvelope()->getRecipients()[0]->getAddress());
        $this->assertStringContainsString('X-Tag: test', $sentMessage->toString());
        $this->assertStringContainsString('X-Tag: foo', $sentMessage->toString());

        $this->assertTrue($mailable->hasTag('test'));
        $this->assertTrue($mailable->hasTag('foo'));
        $this->assertFalse($mailable->hasTag('bar'));
        $mailable->assertHasTag('test');
        $mailable->assertHasTag('foo');
        try {
            $mailable->assertHasTag('bar');
            $this->fail();
        } catch (AssertionFailedError $e) {
            $this->assertSame("Did not see expected tag [bar] in email tags.\nFailed asserting that false is true.", $e->getMessage());
        }
    }

    public function testItCanAttachMultipleFiles()
    {
        $mailable = new WelcomeMailableStub;

        $mailable->attachMany([
            '/forge.svg',
            '/vapor.svg' => ['as' => 'Vapor Logo.svg', 'mime' => 'text/css'],
            new class() implements Attachable
            {
                public function toMailAttachment()
                {
                    return Attachment::fromPath('/foo.jpg')->as('bar')->withMime('image/png');
                }
            },
        ]);

        $this->assertCount(3, $mailable->attachments);
        $this->assertSame([
            'file' => '/forge.svg',
            'options' => [],
        ], $mailable->attachments[0]);
        $this->assertSame([
            'file' => '/vapor.svg',
            'options' => [
                'as' => 'Vapor Logo.svg',
                'mime' => 'text/css',
            ],
        ], $mailable->attachments[1]);
        $this->assertSame([
            'file' => '/foo.jpg',
            'options' => [
                'as' => 'bar',
                'mime' => 'image/png',
            ],
        ], $mailable->attachments[2]);
    }

    public function testItAttachesFilesViaAttachableContractFromPath()
    {
        $mailable = new WelcomeMailableStub;

        $mailable->attach(new class() implements Attachable
        {
            public function toMailAttachment()
            {
                return Attachment::fromPath('/foo.jpg')->as('bar')->withMime('image/png');
            }
        });

        $this->assertSame([
            'file' => '/foo.jpg',
            'options' => [
                'as' => 'bar',
                'mime' => 'image/png',
            ],
        ], $mailable->attachments[0]);
    }

    public function testItAttachesFilesViaAttachableContractFromData()
    {
        $mailable = new WelcomeMailableStub;

        $mailable->attach(new class() implements Attachable
        {
            public function toMailAttachment()
            {
                return Attachment::fromData(fn () => 'bar', 'foo.jpg')->withMime('image/png');
            }
        });

        $this->assertSame([
            'data' => 'bar',
            'name' => 'foo.jpg',
            'options' => [
                'mime' => 'image/png',
            ],
        ], $mailable->rawAttachments[0]);
    }

    public function testItCanCheckForPathBasedAttachments()
    {
        $mailable = new WelcomeMailableStub;
        $mailable->attach('foo.jpg');

        $this->assertTrue($mailable->hasAttachment('foo.jpg'));
        $this->assertTrue($mailable->hasAttachment(Attachment::fromPath('foo.jpg')));
        $this->assertTrue($mailable->hasAttachment(new MailTestAttachable(Attachment::fromPath('foo.jpg'))));

        $this->assertFalse($mailable->hasAttachment('bar.jpg'));
        $this->assertFalse($mailable->hasAttachment(Attachment::fromPath('bar.jpg')));
        $this->assertFalse($mailable->hasAttachment(new MailTestAttachable(Attachment::fromPath('bar.jpg'))));

        $this->assertFalse($mailable->hasAttachment('foo.jpg', ['mime' => 'text/css']));
        $this->assertFalse($mailable->hasAttachment(Attachment::fromPath('foo.jpg')->withMime('text/css')));
        $this->assertFalse($mailable->hasAttachment(new MailTestAttachable(Attachment::fromPath('foo.jpg')->withMime('text/css'))));

        $mailable = new WelcomeMailableStub;
        $mailable->attach('bar.jpg', ['mime' => 'text/css']);

        $this->assertTrue($mailable->hasAttachment('bar.jpg', ['mime' => 'text/css']));
        $this->assertTrue($mailable->hasAttachment(Attachment::fromPath('bar.jpg')->withMime('text/css')));
        $this->assertTrue($mailable->hasAttachment(new MailTestAttachable(Attachment::fromPath('bar.jpg')->withMime('text/css'))));

        $this->assertFalse($mailable->hasAttachment('bar.jpg'));
        $this->assertFalse($mailable->hasAttachment(Attachment::fromPath('bar.jpg')));
        $this->assertFalse($mailable->hasAttachment(new MailTestAttachable(Attachment::fromPath('bar.jpg'))));

        $this->assertFalse($mailable->hasAttachment('bar.jpg', ['mime' => 'text/html']));
        $this->assertFalse($mailable->hasAttachment(Attachment::fromPath('bar.jpg')->withMime('text/html')));
        $this->assertFalse($mailable->hasAttachment(new MailTestAttachable(Attachment::fromPath('bar.jpg')->withMime('text/html'))));
    }

    public function testItCanCheckForAttachmentBasedAttachments()
    {
        $mailable = new WelcomeMailableStub;
        $mailable->attach(Attachment::fromPath('foo.jpg'));

        $this->assertTrue($mailable->hasAttachment('foo.jpg'));
        $this->assertTrue($mailable->hasAttachment(Attachment::fromPath('foo.jpg')));
        $this->assertTrue($mailable->hasAttachment(new MailTestAttachable(Attachment::fromPath('foo.jpg'))));

        $this->assertFalse($mailable->hasAttachment('bar.jpg'));
        $this->assertFalse($mailable->hasAttachment(Attachment::fromPath('bar.jpg')));
        $this->assertFalse($mailable->hasAttachment(new MailTestAttachable(Attachment::fromPath('bar.jpg'))));

        $this->assertFalse($mailable->hasAttachment('foo.jpg', ['mime' => 'text/css']));
        $this->assertFalse($mailable->hasAttachment(Attachment::fromPath('foo.jpg')->withMime('text/css')));
        $this->assertFalse($mailable->hasAttachment(new MailTestAttachable(Attachment::fromPath('foo.jpg')->withMime('text/css'))));

        $mailable = new WelcomeMailableStub;
        $mailable->attach(Attachment::fromPath('bar.jpg')->withMime('text/css'));

        $this->assertTrue($mailable->hasAttachment('bar.jpg', ['mime' => 'text/css']));
        $this->assertTrue($mailable->hasAttachment(Attachment::fromPath('bar.jpg')->withMime('text/css')));
        $this->assertTrue($mailable->hasAttachment(new MailTestAttachable(Attachment::fromPath('bar.jpg')->withMime('text/css'))));

        $this->assertFalse($mailable->hasAttachment('bar.jpg'));
        $this->assertFalse($mailable->hasAttachment(Attachment::fromPath('bar.jpg')));
        $this->assertFalse($mailable->hasAttachment(new MailTestAttachable(Attachment::fromPath('bar.jpg'))));

        $this->assertFalse($mailable->hasAttachment('bar.jpg', ['mime' => 'text/html']));
        $this->assertFalse($mailable->hasAttachment(Attachment::fromPath('bar.jpg')->withMime('text/html')));
        $this->assertFalse($mailable->hasAttachment(new MailTestAttachable(Attachment::fromPath('bar.jpg')->withMime('text/html'))));
    }

    public function testItCanCheckForAttachableBasedAttachments()
    {
        $mailable = new WelcomeMailableStub;
        $mailable->attach(new MailTestAttachable(Attachment::fromPath('foo.jpg')));

        $this->assertTrue($mailable->hasAttachment('foo.jpg'));
        $this->assertTrue($mailable->hasAttachment(Attachment::fromPath('foo.jpg')));
        $this->assertTrue($mailable->hasAttachment(new MailTestAttachable(Attachment::fromPath('foo.jpg'))));

        $this->assertFalse($mailable->hasAttachment('bar.jpg'));
        $this->assertFalse($mailable->hasAttachment(Attachment::fromPath('bar.jpg')));
        $this->assertFalse($mailable->hasAttachment(new MailTestAttachable(Attachment::fromPath('bar.jpg'))));

        $this->assertFalse($mailable->hasAttachment('foo.jpg', ['mime' => 'text/css']));
        $this->assertFalse($mailable->hasAttachment(Attachment::fromPath('foo.jpg')->withMime('text/css')));
        $this->assertFalse($mailable->hasAttachment(new MailTestAttachable(Attachment::fromPath('foo.jpg')->withMime('text/css'))));

        $mailable = new WelcomeMailableStub;
        $mailable->attach(new MailTestAttachable(Attachment::fromPath('bar.jpg')->withMime('text/css')));

        $this->assertTrue($mailable->hasAttachment('bar.jpg', ['mime' => 'text/css']));
        $this->assertTrue($mailable->hasAttachment(Attachment::fromPath('bar.jpg')->withMime('text/css')));
        $this->assertTrue($mailable->hasAttachment(new MailTestAttachable(Attachment::fromPath('bar.jpg')->withMime('text/css'))));

        $this->assertFalse($mailable->hasAttachment('bar.jpg'));
        $this->assertFalse($mailable->hasAttachment(Attachment::fromPath('bar.jpg')));
        $this->assertFalse($mailable->hasAttachment(new MailTestAttachable(Attachment::fromPath('bar.jpg'))));

        $this->assertFalse($mailable->hasAttachment('bar.jpg', ['mime' => 'text/html']));
        $this->assertFalse($mailable->hasAttachment(Attachment::fromPath('bar.jpg')->withMime('text/html')));
        $this->assertFalse($mailable->hasAttachment(new MailTestAttachable(Attachment::fromPath('bar.jpg')->withMime('text/html'))));
    }

    public function testItCanCheckForDataBasedAttachments()
    {
        $mailable = new WelcomeMailableStub;
        $mailable->attachData('data', 'foo.jpg');

        $this->assertTrue($mailable->hasAttachedData('data', 'foo.jpg'));
        $this->assertFalse($mailable->hasAttachedData('xxxx', 'foo.jpg'));
        $this->assertFalse($mailable->hasAttachedData('data', 'bar.jpg'));
        $this->assertFalse($mailable->hasAttachedData('data', 'foo.jpg', ['mime' => 'text/css']));

        $mailable = new WelcomeMailableStub;
        $mailable->attachData('data', 'bar.jpg', ['mime' => 'text/css']);

        $this->assertTrue($mailable->hasAttachedData('data', 'bar.jpg', ['mime' => 'text/css']));
        $this->assertFalse($mailable->hasAttachedData('xxxx', 'bar.jpg', ['mime' => 'text/css']));
        $this->assertFalse($mailable->hasAttachedData('data', 'bar.jpg'));
        $this->assertFalse($mailable->hasAttachedData('data', 'bar.jpg', ['mime' => 'text/html']));

        $mailable = new WelcomeMailableStub;
        $mailable->attach(Attachment::fromData(fn () => 'data', 'foo.jpg'));

        $this->assertTrue($mailable->hasAttachedData('data', 'foo.jpg'));
        $this->assertFalse($mailable->hasAttachedData('xxxx', 'foo.jpg'));
        $this->assertFalse($mailable->hasAttachedData('data', 'bar.jpg'));
        $this->assertFalse($mailable->hasAttachedData('data', 'foo.jpg', ['mime' => 'text/css']));

        $mailable = new WelcomeMailableStub;
        $mailable->attach(Attachment::fromData(fn () => 'data', 'bar.jpg')->withMime('text/css'));

        $this->assertTrue($mailable->hasAttachedData('data', 'bar.jpg', ['mime' => 'text/css']));
        $this->assertFalse($mailable->hasAttachedData('xxxx', 'bar.jpg', ['mime' => 'text/css']));
        $this->assertFalse($mailable->hasAttachedData('data', 'bar.jpg'));
        $this->assertFalse($mailable->hasAttachedData('data', 'bar.jpg', ['mime' => 'text/html']));
    }

    public function testItCanCheckForStorageBasedAttachments()
    {
        $mailable = new WelcomeMailableStub;
        $mailable->attachFromStorageDisk('disk', '/path/to/foo.jpg');

        $this->assertTrue($mailable->hasAttachmentFromStorageDisk('disk', '/path/to/foo.jpg'));
        $this->assertFalse($mailable->hasAttachmentFromStorageDisk('xxxx', '/path/to/foo.jpg'));
        $this->assertFalse($mailable->hasAttachmentFromStorageDisk('disk', 'bar.jpg'));
        $this->assertFalse($mailable->hasAttachmentFromStorageDisk('disk', '/path/to/foo.jpg', 'bar.jpg'));
        $this->assertFalse($mailable->hasAttachmentFromStorageDisk('disk', '/path/to/foo.jpg', null, ['mime' => 'text/css']));

        $mailable = new WelcomeMailableStub;
        $mailable->attachFromStorageDisk('disk', '/path/to/foo.jpg', 'bar.jpg');

        $this->assertTrue($mailable->hasAttachmentFromStorageDisk('disk', '/path/to/foo.jpg', 'bar.jpg'));
        $this->assertFalse($mailable->hasAttachmentFromStorageDisk('xxxx', '/path/to/foo.jpg', 'bar.jpg'));
        $this->assertFalse($mailable->hasAttachmentFromStorageDisk('disk', 'bar.jpg', 'bar.jpg'));
        $this->assertFalse($mailable->hasAttachmentFromStorageDisk('disk', '/path/to/foo.jpg', 'foo.jpg'));
        $this->assertFalse($mailable->hasAttachmentFromStorageDisk('disk', '/path/to/foo.jpg', 'bar.jpg', ['mime' => 'text/css']));

        $mailable = new WelcomeMailableStub;
        $mailable->attachFromStorageDisk('disk', '/path/to/foo.jpg', 'bar.jpg', ['mime' => 'text/css']);

        $this->assertTrue($mailable->hasAttachmentFromStorageDisk('disk', '/path/to/foo.jpg', 'bar.jpg', ['mime' => 'text/css']));
        $this->assertFalse($mailable->hasAttachmentFromStorageDisk('xxxx', '/path/to/foo.jpg', 'bar.jpg', ['mime' => 'text/css']));
        $this->assertFalse($mailable->hasAttachmentFromStorageDisk('disk', 'bar.jpg', 'bar.jpg', ['mime' => 'text/css']));
        $this->assertFalse($mailable->hasAttachmentFromStorageDisk('disk', '/path/to/foo.jpg', 'foo.jpg', ['mime' => 'text/css']));
        $this->assertFalse($mailable->hasAttachmentFromStorageDisk('disk', '/path/to/foo.jpg', 'bar.jpg', ['mime' => 'text/html']));
    }

    public function testAssertHasAttachment()
    {
        Container::getInstance()->instance('mailer', new class
        {
            public function render()
            {
                //
            }
        });

        $mailable = new class() extends Mailable
        {
            public function build()
            {
                //
            }
        };

        try {
            $mailable->assertHasAttachment('/path/to/foo.jpg');
            $this->fail();
        } catch (AssertionFailedError $e) {
            $this->assertSame("Did not find the expected attachment.\nFailed asserting that false is true.", $e->getMessage());
        }

        $mailable = new class() extends Mailable
        {
            public function build()
            {
                $this->attach('/path/to/foo.jpg');
            }
        };

        $mailable->assertHasAttachment('/path/to/foo.jpg');
    }

    public function testAssertHasAttachedData()
    {
        Container::getInstance()->instance('mailer', new class
        {
            public function render()
            {
                //
            }
        });

        $mailable = new class() extends Mailable
        {
            public function build()
            {
                //
            }
        };

        try {
            $mailable->assertHasAttachedData('data', 'foo.jpg');
            $this->fail();
        } catch (AssertionFailedError $e) {
            $this->assertSame("Did not find the expected attachment.\nFailed asserting that false is true.", $e->getMessage());
        }

        $mailable = new class() extends Mailable
        {
            public function build()
            {
                $this->attachData('data', 'foo.jpg');
            }
        };

        $mailable->assertHasAttachedData('data', 'foo.jpg');
    }

    public function testAssertHasAttachmentFromStorage()
    {
        $mailable = new class() extends Mailable
        {
            public function build()
            {
                //
            }
        };

        try {
            $mailable->assertHasAttachmentFromStorage('/path/to/foo.jpg');
            $this->fail();
        } catch (AssertionFailedError $e) {
            $this->assertSame("Did not find the expected attachment.\nFailed asserting that false is true.", $e->getMessage());
        }

        $mailable = new class() extends Mailable
        {
            public function build()
            {
                $this->attachFromStorage('/path/to/foo.jpg');
            }
        };

        $mailable->assertHasAttachmentFromStorage('/path/to/foo.jpg');
    }

    public function testAssertHasSubject()
    {
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

class MailableTestUserStub2
{
    public $name = 'Laravel Framework';
    public $email = 'contact@laravel.com';
}

class MailTestAttachable implements Attachable
{
    public function __construct(protected $attachment)
    {
        //
    }

    public function toMailAttachment()
    {
        return $this->attachment;
    }
}
