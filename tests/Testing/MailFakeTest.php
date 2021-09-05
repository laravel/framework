<?php

namespace Illuminate\Tests\Testing;

use Illuminate\Support\Facades\Mail;
use Illuminate\Tests\Mail\MailableQueueableStub;
use Orchestra\Testbench\TestCase;

class MailFakeTest extends TestCase
{
    public function testMacrosAreCopiedToMailFake()
    {
        Mail::macro('myMacro', function ($param) {
            return $this;
        });

        Mail::fake();

        Mail::myMacro('hello')
            ->to('test@example.com')
            ->send(new MailableQueueableStub());

        self::assertEquals(['myMacro'], array_keys(Mail::mailer()->macros()));
    }
}
