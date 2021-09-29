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

        foreach (['', null, [], false] as $address) {
            $mailable = new WelcomeMailableStub;
            $mailable->to($address);
            $this->assertFalse($mailable->hasTo(new MailableTestUserStub));
            $this->assertFalse($mailable->hasTo($address));
        }
    }

    public function testMailableSetsCcRecipientsCorrectly()
    {
        $mailable = new WelcomeMailableStub;
        $mailable->cc('taylor@laravel.com');
        $this->assertEquals([['name' => null, 'address' => 'taylor@laravel.com']], $mailable->cc);
        $this->assertTrue($mailable->hasCc('taylor@laravel.com'));

        $mailable = new WelcomeMailableStub;
        $mailable->cc('taylor@laravel.com', 'Taylor Otwell');
        $this->assertEquals([['name' => 'Taylor Otwell', 'address' => 'taylor@laravel.com']], $mailable->cc);
        $this->assertTrue($mailable->hasCc('taylor@laravel.com', 'Taylor Otwell'));
        $this->assertTrue($mailable->hasCc('taylor@laravel.com'));

        $mailable = new WelcomeMailableStub;
        $mailable->cc(['taylor@laravel.com']);
        $this->assertEquals([['name' => null, 'address' => 'taylor@laravel.com']], $mailable->cc);
        $this->assertTrue($mailable->hasCc('taylor@laravel.com'));
        $this->assertFalse($mailable->hasCc('taylor@laravel.com', 'Taylor Otwell'));

        $mailable = new WelcomeMailableStub;
        $mailable->cc([['name' => 'Taylor Otwell', 'email' => 'taylor@laravel.com']]);
        $this->assertEquals([['name' => 'Taylor Otwell', 'address' => 'taylor@laravel.com']], $mailable->cc);
        $this->assertTrue($mailable->hasCc('taylor@laravel.com', 'Taylor Otwell'));
        $this->assertTrue($mailable->hasCc('taylor@laravel.com'));

        $mailable = new WelcomeMailableStub;
        $mailable->cc(new MailableTestUserStub);
        $this->assertEquals([['name' => 'Taylor Otwell', 'address' => 'taylor@laravel.com']], $mailable->cc);
        $this->assertTrue($mailable->hasCc(new MailableTestUserStub));
        $this->assertTrue($mailable->hasCc('taylor@laravel.com'));

        $mailable = new WelcomeMailableStub;
        $mailable->cc(collect([new MailableTestUserStub]));
        $this->assertEquals([['name' => 'Taylor Otwell', 'address' => 'taylor@laravel.com']], $mailable->cc);
        $this->assertTrue($mailable->hasCc(new MailableTestUserStub));
        $this->assertTrue($mailable->hasCc('taylor@laravel.com'));

        $mailable = new WelcomeMailableStub;
        $mailable->cc(collect([new MailableTestUserStub, new MailableTestUserStub]));
        $this->assertEquals([
            ['name' => 'Taylor Otwell', 'address' => 'taylor@laravel.com'],
            ['name' => 'Taylor Otwell', 'address' => 'taylor@laravel.com'],
        ], $mailable->cc);
        $this->assertTrue($mailable->hasCc(new MailableTestUserStub));
        $this->assertTrue($mailable->hasCc('taylor@laravel.com'));

        $mailable = new WelcomeMailableStub;
        $mailable->cc(['taylor@laravel.com', 'not-taylor@laravel.com']);
        $this->assertEquals([
            ['name' => null, 'address' => 'taylor@laravel.com'],
            ['name' => null, 'address' => 'not-taylor@laravel.com'],
        ], $mailable->cc);
        $this->assertTrue($mailable->hasCc('taylor@laravel.com'));
        $this->assertTrue($mailable->hasCc('not-taylor@laravel.com'));

        foreach (['', null, [], false] as $address) {
            $mailable = new WelcomeMailableStub;
            $mailable->cc($address);
            $this->assertFalse($mailable->hasCc(new MailableTestUserStub));
            $this->assertFalse($mailable->hasCc($address));
        }
    }

    public function testMailableSetsBccRecipientsCorrectly()
    {
        $mailable = new WelcomeMailableStub;
        $mailable->bcc('taylor@laravel.com');
        $this->assertEquals([['name' => null, 'address' => 'taylor@laravel.com']], $mailable->bcc);
        $this->assertTrue($mailable->hasBcc('taylor@laravel.com'));

        $mailable = new WelcomeMailableStub;
        $mailable->bcc('taylor@laravel.com', 'Taylor Otwell');
        $this->assertEquals([['name' => 'Taylor Otwell', 'address' => 'taylor@laravel.com']], $mailable->bcc);
        $this->assertTrue($mailable->hasBcc('taylor@laravel.com', 'Taylor Otwell'));
        $this->assertTrue($mailable->hasBcc('taylor@laravel.com'));

        $mailable = new WelcomeMailableStub;
        $mailable->bcc(['taylor@laravel.com']);
        $this->assertEquals([['name' => null, 'address' => 'taylor@laravel.com']], $mailable->bcc);
        $this->assertTrue($mailable->hasBcc('taylor@laravel.com'));
        $this->assertFalse($mailable->hasBcc('taylor@laravel.com', 'Taylor Otwell'));

        $mailable = new WelcomeMailableStub;
        $mailable->bcc([['name' => 'Taylor Otwell', 'email' => 'taylor@laravel.com']]);
        $this->assertEquals([['name' => 'Taylor Otwell', 'address' => 'taylor@laravel.com']], $mailable->bcc);
        $this->assertTrue($mailable->hasBcc('taylor@laravel.com', 'Taylor Otwell'));
        $this->assertTrue($mailable->hasBcc('taylor@laravel.com'));

        $mailable = new WelcomeMailableStub;
        $mailable->bcc(new MailableTestUserStub);
        $this->assertEquals([['name' => 'Taylor Otwell', 'address' => 'taylor@laravel.com']], $mailable->bcc);
        $this->assertTrue($mailable->hasBcc(new MailableTestUserStub));
        $this->assertTrue($mailable->hasBcc('taylor@laravel.com'));

        $mailable = new WelcomeMailableStub;
        $mailable->bcc(collect([new MailableTestUserStub]));
        $this->assertEquals([['name' => 'Taylor Otwell', 'address' => 'taylor@laravel.com']], $mailable->bcc);
        $this->assertTrue($mailable->hasBcc(new MailableTestUserStub));
        $this->assertTrue($mailable->hasBcc('taylor@laravel.com'));

        $mailable = new WelcomeMailableStub;
        $mailable->bcc(collect([new MailableTestUserStub, new MailableTestUserStub]));
        $this->assertEquals([
            ['name' => 'Taylor Otwell', 'address' => 'taylor@laravel.com'],
            ['name' => 'Taylor Otwell', 'address' => 'taylor@laravel.com'],
        ], $mailable->bcc);
        $this->assertTrue($mailable->hasBcc(new MailableTestUserStub));
        $this->assertTrue($mailable->hasBcc('taylor@laravel.com'));

        $mailable = new WelcomeMailableStub;
        $mailable->bcc(['taylor@laravel.com', 'not-taylor@laravel.com']);
        $this->assertEquals([
            ['name' => null, 'address' => 'taylor@laravel.com'],
            ['name' => null, 'address' => 'not-taylor@laravel.com'],
        ], $mailable->bcc);
        $this->assertTrue($mailable->hasBcc('taylor@laravel.com'));
        $this->assertTrue($mailable->hasBcc('not-taylor@laravel.com'));

        foreach (['', null, [], false] as $address) {
            $mailable = new WelcomeMailableStub;
            $mailable->bcc($address);
            $this->assertFalse($mailable->hasBcc(new MailableTestUserStub));
            $this->assertFalse($mailable->hasBcc($address));
        }
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

        foreach (['', null, [], false] as $address) {
            $mailable = new WelcomeMailableStub;
            $mailable->replyTo($address);
            $this->assertFalse($mailable->hasReplyTo(new MailableTestUserStub));
            $this->assertFalse($mailable->hasReplyTo($address));
        }
    }

    public function testMailableSetsFromCorrectly()
    {
        $mailable = new WelcomeMailableStub;
        $mailable->from('taylor@laravel.com');
        $this->assertEquals([['name' => null, 'address' => 'taylor@laravel.com']], $mailable->from);
        $this->assertTrue($mailable->hasFrom('taylor@laravel.com'));

        $mailable = new WelcomeMailableStub;
        $mailable->from('taylor@laravel.com', 'Taylor Otwell');
        $this->assertEquals([['name' => 'Taylor Otwell', 'address' => 'taylor@laravel.com']], $mailable->from);
        $this->assertTrue($mailable->hasFrom('taylor@laravel.com', 'Taylor Otwell'));
        $this->assertTrue($mailable->hasFrom('taylor@laravel.com'));

        $mailable = new WelcomeMailableStub;
        $mailable->from(['taylor@laravel.com']);
        $this->assertEquals([['name' => null, 'address' => 'taylor@laravel.com']], $mailable->from);
        $this->assertTrue($mailable->hasFrom('taylor@laravel.com'));
        $this->assertFalse($mailable->hasFrom('taylor@laravel.com', 'Taylor Otwell'));

        $mailable = new WelcomeMailableStub;
        $mailable->from([['name' => 'Taylor Otwell', 'email' => 'taylor@laravel.com']]);
        $this->assertEquals([['name' => 'Taylor Otwell', 'address' => 'taylor@laravel.com']], $mailable->from);
        $this->assertTrue($mailable->hasFrom('taylor@laravel.com', 'Taylor Otwell'));
        $this->assertTrue($mailable->hasFrom('taylor@laravel.com'));

        $mailable = new WelcomeMailableStub;
        $mailable->from(new MailableTestUserStub);
        $this->assertEquals([['name' => 'Taylor Otwell', 'address' => 'taylor@laravel.com']], $mailable->from);
        $this->assertTrue($mailable->hasFrom(new MailableTestUserStub));
        $this->assertTrue($mailable->hasFrom('taylor@laravel.com'));

        $mailable = new WelcomeMailableStub;
        $mailable->from(collect([new MailableTestUserStub]));
        $this->assertEquals([['name' => 'Taylor Otwell', 'address' => 'taylor@laravel.com']], $mailable->from);
        $this->assertTrue($mailable->hasFrom(new MailableTestUserStub));
        $this->assertTrue($mailable->hasFrom('taylor@laravel.com'));

        $mailable = new WelcomeMailableStub;
        $mailable->from(collect([new MailableTestUserStub, new MailableTestUserStub]));
        $this->assertEquals([
            ['name' => 'Taylor Otwell', 'address' => 'taylor@laravel.com'],
            ['name' => 'Taylor Otwell', 'address' => 'taylor@laravel.com'],
        ], $mailable->from);
        $this->assertTrue($mailable->hasFrom(new MailableTestUserStub));
        $this->assertTrue($mailable->hasFrom('taylor@laravel.com'));

        foreach (['', null, [], false] as $address) {
            $mailable = new WelcomeMailableStub;
            $mailable->from($address);
            $this->assertFalse($mailable->hasFrom(new MailableTestUserStub));
            $this->assertFalse($mailable->hasFrom($address));
        }
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
