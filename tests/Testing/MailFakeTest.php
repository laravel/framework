<?php

namespace Illuminate\Tests\Testing;

use Illuminate\Support\Facades\Mail;
use Orchestra\Testbench\TestCase;

class MailFakeTest extends TestCase
{
    public function testMacrosCanBeUsedWithMailFake()
    {
        Mail::macro('foo', function () {
            return 'bar';
        });

        Mail::fake();

        $this->assertSame(
            'bar', Mail::foo()
        );
    }
}
