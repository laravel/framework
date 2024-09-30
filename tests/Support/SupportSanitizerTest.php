<?php

namespace Illuminate\Tests\Support;

use Illuminate\Support\Sanitizer;
use PHPUnit\Framework\TestCase;

class SupportSanitizerTest extends TestCase
{
    public function testRemove()
    {
        $this->assertSame('Fbar', Sanitizer::remove('o', 'Foobar'));
        $this->assertSame('Foo', Sanitizer::remove('bar', 'Foobar'));
        $this->assertSame('oobar', Sanitizer::remove('F', 'Foobar'));
        $this->assertSame('Foobar', Sanitizer::remove('f', 'Foobar'));
        $this->assertSame('oobar', Sanitizer::remove('f', 'Foobar', false));

        $this->assertSame('Fbr', Sanitizer::remove(['o', 'a'], 'Foobar'));
        $this->assertSame('Fooar', Sanitizer::remove(['f', 'b'], 'Foobar'));
        $this->assertSame('ooar', Sanitizer::remove(['f', 'b'], 'Foobar', false));
        $this->assertSame('Foobar', Sanitizer::remove(['f', '|'], 'Foo|bar'));
    }

    public function testTrim()
    {
        $this->assertSame('foo bar', Sanitizer::trim('   foo bar   '));
        $this->assertSame('foo bar', Sanitizer::trim('foo bar   '));
        $this->assertSame('foo bar', Sanitizer::trim('   foo bar'));
        $this->assertSame('foo bar', Sanitizer::trim('foo bar'));
        $this->assertSame(' foo bar ', Sanitizer::trim(' foo bar ', ''));
        $this->assertSame('foo bar', Sanitizer::trim(' foo bar ', ' '));
        $this->assertSame('foo  bar', Sanitizer::trim('-foo  bar_', '-_'));

        $this->assertSame('foo    bar', Sanitizer::trim(' foo    bar '));

        $this->assertSame('123', Sanitizer::trim('   123    '));
        $this->assertSame('だ', Sanitizer::trim('だ'));
        $this->assertSame('ム', Sanitizer::trim('ム'));
        $this->assertSame('だ', Sanitizer::trim('   だ    '));
        $this->assertSame('ム', Sanitizer::trim('   ム    '));

        $this->assertSame(
            'foo bar',
            Sanitizer::trim('
                foo bar
            ')
        );
        $this->assertSame(
            'foo
                bar',
            Sanitizer::trim('
                foo
                bar
            ')
        );

        $this->assertSame("\xE9", Sanitizer::trim(" \xE9 "));

        $trimDefaultChars = [' ', "\n", "\r", "\t", "\v", "\0"];

        foreach ($trimDefaultChars as $char) {
            $this->assertSame('', Sanitizer::trim(" {$char} "));
            $this->assertSame(trim(" {$char} "), Sanitizer::trim(" {$char} "));

            $this->assertSame('foo bar', Sanitizer::trim("{$char} foo bar {$char}"));
            $this->assertSame(trim("{$char} foo bar {$char}"), Sanitizer::trim("{$char} foo bar {$char}"));
        }
    }

    public function testLtrim()
    {
        $this->assertSame('foo    bar ', Sanitizer::ltrim(' foo    bar '));

        $this->assertSame('123    ', Sanitizer::ltrim('   123    '));
        $this->assertSame('だ', Sanitizer::ltrim('だ'));
        $this->assertSame('ム', Sanitizer::ltrim('ム'));
        $this->assertSame('だ    ', Sanitizer::ltrim('   だ    '));
        $this->assertSame('ム    ', Sanitizer::ltrim('   ム    '));

        $this->assertSame(
            'foo bar
            ',
            Sanitizer::ltrim('
                foo bar
            ')
        );
        $this->assertSame("\xE9 ", Sanitizer::ltrim(" \xE9 "));

        $ltrimDefaultChars = [' ', "\n", "\r", "\t", "\v", "\0"];

        foreach ($ltrimDefaultChars as $char) {
            $this->assertSame('', Sanitizer::ltrim(" {$char} "));
            $this->assertSame(ltrim(" {$char} "), Sanitizer::ltrim(" {$char} "));

            $this->assertSame("foo bar {$char}", Sanitizer::ltrim("{$char} foo bar {$char}"));
            $this->assertSame(ltrim("{$char} foo bar {$char}"), Sanitizer::ltrim("{$char} foo bar {$char}"));
        }
    }

    public function testRtrim()
    {
        $this->assertSame(' foo    bar', Sanitizer::rtrim(' foo    bar '));

        $this->assertSame('   123', Sanitizer::rtrim('   123    '));
        $this->assertSame('だ', Sanitizer::rtrim('だ'));
        $this->assertSame('ム', Sanitizer::rtrim('ム'));
        $this->assertSame('   だ', Sanitizer::rtrim('   だ    '));
        $this->assertSame('   ム', Sanitizer::rtrim('   ム    '));

        $this->assertSame(
            '
                foo bar',
            Sanitizer::rtrim('
                foo bar
            ')
        );

        $this->assertSame(" \xE9", Sanitizer::rtrim(" \xE9 "));

        $rtrimDefaultChars = [' ', "\n", "\r", "\t", "\v", "\0"];

        foreach ($rtrimDefaultChars as $char) {
            $this->assertSame('', Sanitizer::rtrim(" {$char} "));
            $this->assertSame(rtrim(" {$char} "), Sanitizer::rtrim(" {$char} "));

            $this->assertSame("{$char} foo bar", Sanitizer::rtrim("{$char} foo bar {$char}"));
            $this->assertSame(rtrim("{$char} foo bar {$char}"), Sanitizer::rtrim("{$char} foo bar {$char}"));
        }
    }

    public function testSquish()
    {
        $this->assertSame('laravel php framework', Sanitizer::squish(' laravel   php  framework '));
        $this->assertSame('laravel php framework', Sanitizer::squish("laravel\t\tphp\n\nframework"));
        $this->assertSame('laravel php framework', Sanitizer::squish('
            laravel
            php
            framework
        '));
        $this->assertSame('laravel php framework', Sanitizer::squish('   laravel   php   framework   '));
        $this->assertSame('123', Sanitizer::squish('   123    '));
        $this->assertSame('だ', Sanitizer::squish('だ'));
        $this->assertSame('ム', Sanitizer::squish('ム'));
        $this->assertSame('だ', Sanitizer::squish('   だ    '));
        $this->assertSame('ム', Sanitizer::squish('   ム    '));
        $this->assertSame('laravel php framework', Sanitizer::squish('laravelㅤㅤㅤphpㅤframework'));
        $this->assertSame('laravel php framework', Sanitizer::squish('laravelᅠᅠᅠᅠᅠᅠᅠᅠᅠᅠphpᅠᅠframework'));
    }
}
