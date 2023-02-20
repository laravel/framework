<?php

namespace Illuminate\Tests\Support;

use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Mail;
use Orchestra\Testbench\TestCase;

class SupportMailTest extends TestCase
{
    public function testItRegisterAndCallMacros(): void
    {
        Mail::macro('test', fn (string $str) => $str === 'foo'
            ? 'it works!'
            : 'it failed.',
        );

        $this->assertEquals('it works!', Mail::test('foo'));
    }

    public function testItRegisterAndCallMacrosWhenFaked(): void
    {
        Mail::macro('test', fn (string $str) => $str === 'foo'
            ? 'it works!'
            : 'it failed.',
        );

        Mail::fake();

        $this->assertEquals('it works!', Mail::test('foo'));
    }

    public function testEmailSent(): void
    {
        Mail::fake();
        Mail::assertNothingSent();

        Mail::to('hello@laravel.com')->send(new TestMail());

        Mail::assertSent(TestMail::class);
    }
}


class TestMail extends Mailable
{
    public function build(): self
    {
        return $this->view('view');
    }
}
