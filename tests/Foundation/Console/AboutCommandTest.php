<?php

namespace Illuminate\Tests\Foundation\Console;

use Illuminate\Foundation\Console\AboutCommand;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class AboutCommandTest extends TestCase
{
    /**
     * @param  \Closure(bool):mixed  $format
     * @param  mixed  $expected
     */
    #[DataProvider('cliDataProvider')]
    public function testItCanFormatForCliInterface($format, $expected)
    {
        $this->assertSame($expected, value($format, false));
    }

    public static function cliDataProvider()
    {
        yield [AboutCommand::format(true, console: fn ($value) => $value === true ? 'YES' : 'NO'), 'YES'];
        yield [AboutCommand::format(false, console: fn ($value) => $value === true ? 'YES' : 'NO'), 'NO'];
    }

    /**
     * @param  \Closure(bool):mixed  $format
     * @param  mixed  $expected
     */
    #[DataProvider('jsonDataProvider')]
    public function testItCanFormatForJsonInterface($format, $expected)
    {
        $this->assertSame($expected, value($format, true));
    }

    public static function jsonDataProvider()
    {
        yield [AboutCommand::format(true, json: fn ($value) => $value === true ? 'YES' : 'NO'), 'YES'];
        yield [AboutCommand::format(false, json: fn ($value) => $value === true ? 'YES' : 'NO'), 'NO'];
    }
}
