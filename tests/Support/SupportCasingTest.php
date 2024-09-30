<?php

namespace Illuminate\Tests\Support;

use Illuminate\Support\Casing;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class SupportCasingTest extends TestCase
{

    public function testConvertCase()
    {
        // Upper Case Conversion
        $this->assertSame('HELLO', Casing::convertCase('hello', MB_CASE_UPPER));
        $this->assertSame('WORLD', Casing::convertCase('WORLD', MB_CASE_UPPER));

        // Lower Case Conversion
        $this->assertSame('hello', Casing::convertCase('HELLO', MB_CASE_LOWER));
        $this->assertSame('world', Casing::convertCase('WORLD', MB_CASE_LOWER));

        // Case Folding
        $this->assertSame('hello', Casing::convertCase('HeLLo', MB_CASE_FOLD));
        $this->assertSame('world', Casing::convertCase('WoRLD', MB_CASE_FOLD));

        // Multi-byte String
        $this->assertSame('ÜÖÄ', Casing::convertCase('üöä', MB_CASE_UPPER, 'UTF-8'));
        $this->assertSame('üöä', Casing::convertCase('ÜÖÄ', MB_CASE_LOWER, 'UTF-8'));

        // Unsupported Mode
        $this->expectException(\ValueError::class);
        Casing::convertCase('Hello', -1);
    }

    public function testKebab()
    {
        $this->assertSame('laravel-php-framework', Casing::kebab('LaravelPhpFramework'));
        $this->assertSame('laravel-php-framework', Casing::kebab('Laravel Php Framework'));
        $this->assertSame('laravel❤-php-framework', Casing::kebab('Laravel ❤ Php Framework'));
        $this->assertSame('', Casing::kebab(''));
    }

    public function testLower()
    {
        $this->assertSame('foo bar baz', Casing::lower('FOO BAR BAZ'));
        $this->assertSame('foo bar baz', Casing::lower('fOo Bar bAz'));
    }

    public function testUpper()
    {
        $this->assertSame('FOO BAR BAZ', Casing::upper('foo bar baz'));
        $this->assertSame('FOO BAR BAZ', Casing::upper('foO bAr BaZ'));
    }

    public function testCamel(): void
    {
        $this->assertSame('laravelPHPFramework', Casing::camel('Laravel_p_h_p_framework'));
        $this->assertSame('laravelPhpFramework', Casing::camel('Laravel_php_framework'));
        $this->assertSame('laravelPhPFramework', Casing::camel('Laravel-phP-framework'));
        $this->assertSame('laravelPhpFramework', Casing::camel('Laravel  -_-  php   -_-   framework   '));

        $this->assertSame('fooBar', Casing::camel('FooBar'));
        $this->assertSame('fooBar', Casing::camel('foo_bar'));
        $this->assertSame('fooBar', Casing::camel('foo_bar')); // test cache
        $this->assertSame('fooBarBaz', Casing::camel('Foo-barBaz'));
        $this->assertSame('fooBarBaz', Casing::camel('foo-bar_baz'));

        $this->assertSame('', Casing::camel(''));
        $this->assertSame('lARAVELPHPFRAMEWORK', Casing::camel('LARAVEL_PHP_FRAMEWORK'));
        $this->assertSame('laravelPhpFramework', Casing::camel('   laravel   php   framework   '));

        $this->assertSame('foo1Bar', Casing::camel('foo1_bar'));
        $this->assertSame('1FooBar', Casing::camel('1 foo bar'));
    }

    public function testStringTitle()
    {
        $this->assertSame('Jefferson Costella', Casing::title('jefferson costella'));
        $this->assertSame('Jefferson Costella', Casing::title('jefFErson coSTella'));

        $this->assertSame('', Casing::title(''));
        $this->assertSame('123 Laravel', Casing::title('123 laravel'));
        $this->assertSame('❤Laravel', Casing::title('❤laravel'));
        $this->assertSame('Laravel ❤', Casing::title('laravel ❤'));
        $this->assertSame('Laravel123', Casing::title('laravel123'));
        $this->assertSame('Laravel123', Casing::title('Laravel123'));

        $longString = 'lorem ipsum '.str_repeat('dolor sit amet ', 1000);
        $expectedResult = 'Lorem Ipsum Dolor Sit Amet '.str_repeat('Dolor Sit Amet ', 999);
        $this->assertSame($expectedResult, Casing::title($longString));
    }

    public function testStringHeadline()
    {
        $this->assertSame('Jefferson Costella', Casing::headline('jefferson costella'));
        $this->assertSame('Jefferson Costella', Casing::headline('jefFErson coSTella'));
        $this->assertSame('Jefferson Costella Uses Laravel', Casing::headline('jefferson_costella uses-_Laravel'));
        $this->assertSame('Jefferson Costella Uses Laravel', Casing::headline('jefferson_costella uses__Laravel'));

        $this->assertSame('Laravel P H P Framework', Casing::headline('laravel_p_h_p_framework'));
        $this->assertSame('Laravel P H P Framework', Casing::headline('laravel _p _h _p _framework'));
        $this->assertSame('Laravel Php Framework', Casing::headline('laravel_php_framework'));
        $this->assertSame('Laravel Ph P Framework', Casing::headline('laravel-phP-framework'));
        $this->assertSame('Laravel Php Framework', Casing::headline('laravel  -_-  php   -_-   framework   '));

        $this->assertSame('Foo Bar', Casing::headline('fooBar'));
        $this->assertSame('Foo Bar', Casing::headline('foo_bar'));
        $this->assertSame('Foo Bar Baz', Casing::headline('foo-barBaz'));
        $this->assertSame('Foo Bar Baz', Casing::headline('foo-bar_baz'));

        $this->assertSame('Öffentliche Überraschungen', Casing::headline('öffentliche-überraschungen'));
        $this->assertSame('Öffentliche Überraschungen', Casing::headline('-_öffentliche_überraschungen_-'));
        $this->assertSame('Öffentliche Überraschungen', Casing::headline('-öffentliche überraschungen'));

        $this->assertSame('Sind Öde Und So', Casing::headline('sindÖdeUndSo'));

        $this->assertSame('Orwell 1984', Casing::headline('orwell 1984'));
        $this->assertSame('Orwell 1984', Casing::headline('orwell   1984'));
        $this->assertSame('Orwell 1984', Casing::headline('-orwell-1984 -'));
        $this->assertSame('Orwell 1984', Casing::headline(' orwell_- 1984 '));
    }

    public function testLcfirst()
    {
        $this->assertSame('laravel', Casing::lcfirst('Laravel'));
        $this->assertSame('laravel framework', Casing::lcfirst('Laravel framework'));
        $this->assertSame('мама', Casing::lcfirst('Мама'));
        $this->assertSame('мама мыла раму', Casing::lcfirst('Мама мыла раму'));
    }

    public function testUcfirst()
    {
        $this->assertSame('Laravel', Casing::ucfirst('laravel'));
        $this->assertSame('Laravel framework', Casing::ucfirst('laravel framework'));
        $this->assertSame('Мама', Casing::ucfirst('мама'));
        $this->assertSame('Мама мыла раму', Casing::ucfirst('мама мыла раму'));
    }

    public function testFlushCache()
    {
        $reflection = new ReflectionClass(Casing::class);
        $property = $reflection->getProperty('snakeCache');

        Casing::flushCache();
        $this->assertEmpty($property->getValue());

        Casing::snake('Taylor Otwell');
        $this->assertNotEmpty($property->getValue());

        Casing::flushCache();
        $this->assertEmpty($property->getValue());
    }

    public function testSnake()
    {
        $this->assertSame('laravel_p_h_p_framework', Casing::snake('LaravelPHPFramework'));
        $this->assertSame('laravel_php_framework', Casing::snake('LaravelPhpFramework'));
        $this->assertSame('laravel php framework', Casing::snake('LaravelPhpFramework', ' '));
        $this->assertSame('laravel_php_framework', Casing::snake('Laravel Php Framework'));
        $this->assertSame('laravel_php_framework', Casing::snake('Laravel    Php      Framework   '));
        // ensure cache keys don't overlap
        $this->assertSame('laravel__php__framework', Casing::snake('LaravelPhpFramework', '__'));
        $this->assertSame('laravel_php_framework_', Casing::snake('LaravelPhpFramework_', '_'));
        $this->assertSame('laravel_php_framework', Casing::snake('laravel php Framework'));
        $this->assertSame('laravel_php_frame_work', Casing::snake('laravel php FrameWork'));
        // prevent breaking changes
        $this->assertSame('foo-bar', Casing::snake('foo-bar'));
        $this->assertSame('foo-_bar', Casing::snake('Foo-Bar'));
        $this->assertSame('foo__bar', Casing::snake('Foo_Bar'));
        $this->assertSame('żółtałódka', Casing::snake('ŻółtaŁódka'));
    }

    public function testStudly()
    {
        $this->assertSame('LaravelPHPFramework', Casing::studly('laravel_p_h_p_framework'));
        $this->assertSame('LaravelPhpFramework', Casing::studly('laravel_php_framework'));
        $this->assertSame('LaravelPhPFramework', Casing::studly('laravel-phP-framework'));
        $this->assertSame('LaravelPhpFramework', Casing::studly('laravel  -_-  php   -_-   framework   '));

        $this->assertSame('FooBar', Casing::studly('fooBar'));
        $this->assertSame('FooBar', Casing::studly('foo_bar'));
        $this->assertSame('FooBar', Casing::studly('foo_bar')); // test cache
        $this->assertSame('FooBarBaz', Casing::studly('foo-barBaz'));
        $this->assertSame('FooBarBaz', Casing::studly('foo-bar_baz'));

        $this->assertSame('ÖffentlicheÜberraschungen', Casing::studly('öffentliche-überraschungen'));
    }
}
