<?php

namespace Illuminate\Tests\Support;

use Illuminate\Support\Facades\Mail;
use Orchestra\Testbench\TestCase;

class SupportMailTest extends TestCase
{
    public function testItRegisterAndCallMacros()
    {
        Mail::macro('test', fn (string $str) => $str === 'foo'
            ? 'it works!'
            : 'it failed.',
        );

        $this->assertEquals('it works!', Mail::test('foo'));
    }

    public function testItRegisterAndCallMacrosWhenFaked()
    {
        Mail::macro('test', fn (string $str) => $str === 'foo'
            ? 'it works!'
            : 'it failed.',
        );

        Mail::fake();

        $this->assertEquals('it works!', Mail::test('foo'));
    }
}
