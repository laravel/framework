<?php

namespace Illuminate\Tests\Support;

use Illuminate\Support\Str;
use Illuminate\Support\StrProxy;
use PHPUnit\Framework\TestCase;

class SupportStrProxyTest extends TestCase
{
    /** @test */
    public function str_after(): void
    {
        $this->assertSame(
            Str::after('This is my name', 'This is'),
            (new StrProxy('This is my name'))->after('This is')->get()
        );
    }

    /** @test */
    public function str_ascii(): void
    {
        $this->assertSame(Str::ascii('@'), (new StrProxy('@'))->ascii()->get());
        $this->assertSame(Str::ascii('ü'), (new StrProxy('ü'))->ascii()->get());
        $this->assertSame(Str::ascii('х Х щ Щ ъ Ъ ь Ь', 'bg'), (new StrProxy('х Х щ Щ ъ Ъ ь Ь'))->ascii('bg')->get());
        $this->assertSame(Str::ascii('ä ö ü Ä Ö Ü', 'de'), (new StrProxy('ä ö ü Ä Ö Ü'))->ascii('de')->get());
    }

    /** @test */
    public function str_before(): void
    {
        $this->assertSame(
            Str::before('This is my name', 'my name'),
            (new StrProxy('This is my name'))->before('my name')->get()
        );
    }

    /** @test */
    public function str_camel(): void
    {
        $this->assertSame(Str::camel('foo_bar'), (new StrProxy('foo_bar'))->camel()->get());
    }

    /** @test */
    public function str_contains(): void
    {
        $this->assertSame(
            Str::contains('This is my name', 'my'),
            (new StrProxy('This is my name'))->contains('my')
        );
        $this->assertSame(
            Str::contains('This is my name', ['my', 'foo']),
            (new StrProxy('This is my name'))->contains(['my', 'foo'])
        );
    }

    /** @test */
    public function str_contains_all(): void
    {
        $this->assertSame(
            Str::containsAll('This is my name', ['my', 'name']),
            (new StrProxy('This is my name'))->containsAll(['my', 'name'])
        );
    }

    /** @test */
    public function str_ends_with(): void
    {
        $this->assertSame(
            Str::endsWith('This is my name', 'name'),
            (new StrProxy('This is my name'))->endsWith('name')
        );
    }

    /** @test */
    public function str_finish(): void
    {
        $this->assertSame(Str::finish('this/string', '/'), (new StrProxy('this/string'))->finish('/')->get());
        $this->assertSame(Str::finish('this/string/', '/'), (new StrProxy('this/string/'))->finish('/')->get());
    }

    /** @test */
    public function str_is(): void
    {
        $this->assertSame(Str::is('foo*', 'foobar'), (new StrProxy('foobar'))->is('foo*'));
        $this->assertSame(Str::is('baz*', 'foobar'), (new StrProxy('foobar'))->is('baz*'));
    }

    /** @test */
    public function str_kebab(): void
    {
        $this->assertSame(Str::kebab('fooBar'), (new StrProxy('fooBar'))->kebab()->get());
    }

    /** @test */
    public function str_length(): void
    {
        $this->assertSame(Str::length('foo bar baz'), (new StrProxy('foo bar baz'))->length());
        $this->assertSame(Str::length('foo bar baz', 'UTF-8'), (new StrProxy('foo bar baz'))->length('UTF-8'));
    }

    /** @test */
    public function str_limit(): void
    {
        $this->assertSame(
            Str::limit('The quick brown fox jumps over the lazy dog', 20),
            (new StrProxy('The quick brown fox jumps over the lazy dog'))->limit(20)->get()
        );
        $this->assertSame(
            Str::limit('The quick brown fox jumps over the lazy dog', 20, ' (...)'),
            (new StrProxy('The quick brown fox jumps over the lazy dog'))->limit(20, ' (...)')->get()
        );
    }

    /** @test */
    public function str_lower(): void
    {
        $this->assertSame(Str::lower('FOO BAR BAZ'), (new StrProxy('FOO BAR BAZ'))->lower()->get());
        $this->assertSame(Str::lower('fOo Bar bAz'), (new StrProxy('fOo Bar bAz'))->lower()->get());
    }

    /** @test */
    public function str_ordered_uuid(): void
    {
        $pattern = '/\w{8}-\w{4}-\w{4}-\w{4}-\w{12}/';
        $this->assertRegExp($pattern, (string) Str::orderedUuid());
        $this->assertRegExp($pattern, (string) (new StrProxy(''))->orderedUuid());
    }

    /** @test */
    public function str_parse_callback(): void
    {
        $this->assertSame(
            Str::parseCallback('Class@method', 'foo'),
            (new StrProxy('Class@method'))->parseCallback('foo')
        );
        $this->assertSame(
            Str::parseCallback('Class', 'foo'),
            (new StrProxy('Class'))->parseCallback('foo')
        );
    }

    /** @test */
    public function str_plural(): void
    {
        $this->assertSame(Str::plural('car'), (new StrProxy('car'))->plural()->get());
        $this->assertSame(Str::plural('child'), (new StrProxy('child'))->plural()->get());
        $this->assertSame(Str::plural('child', 2), (new StrProxy('child'))->plural(2)->get());
        $this->assertSame(Str::plural('child', 1), (new StrProxy('child'))->plural(1)->get());
    }

    /** @test */
    public function str_random(): void
    {
        $pattern = '/\w{40}/';
        $this->assertRegExp($pattern, Str::random(40));
        // StrProxy
        $string = new StrProxy('');
        $this->assertRegExp($pattern, $string->random(40)->get());
    }

    /** @test */
    public function str_replace_array(): void
    {
        $this->assertSame(
            Str::replaceArray('?', ['8:30', '9:00'], 'The event will take place between ? and ?'),
            (new StrProxy('The event will take place between ? and ?'))->replaceArray('?', ['8:30', '9:00'])->get()
        );
    }

    /** @test */
    public function str_replace_first(): void
    {
        $this->assertSame(
            Str::replaceFirst('the', 'a', 'the quick brown fox jumps over the lazy dog'),
            (new StrProxy('the quick brown fox jumps over the lazy dog'))->replaceFirst('the', 'a')->get()
        );
    }

    /** @test */
    public function str_replace_last(): void
    {
        $this->assertSame(
            Str::replaceLast('the', 'a', 'the quick brown fox jumps over the lazy dog'),
            (new StrProxy('the quick brown fox jumps over the lazy dog'))->replaceLast('the', 'a')->get()
        );
    }

    /** @test */
    public function str_singular(): void
    {
        $this->assertSame(Str::singular('cars'), (new StrProxy('cars'))->singular()->get());
        $this->assertSame(Str::singular('children'), (new StrProxy('children'))->singular()->get());
    }

    /** @test */
    public function str_slug(): void
    {
        $this->assertSame(
            Str::slug('Laravel 5 Framework', '-'),
            (new StrProxy('Laravel 5 Framework'))->slug('-')->get()
        );
    }

    /** @test */
    public function str_snake(): void
    {
        $this->assertSame(Str::snake('fooBar'), (new StrProxy('fooBar'))->snake()->get());
    }

    /** @test */
    public function str_start(): void
    {
        $this->assertSame(Str::start('this/string', '/'), (new StrProxy('this/string'))->start('/')->get());
        $this->assertSame(Str::start('/this/string', '/'), (new StrProxy('/this/string'))->start('/')->get());
    }

    /** @test */
    public function str_starts_with(): void
    {
        $this->assertSame(
            Str::startsWith('This is my name', 'This'),
            (new StrProxy('This is my name'))->startsWith('This')
        );
    }

    /** @test */
    public function str_studly(): void
    {
        $this->assertSame(Str::studly('foo_bar'), (new StrProxy('foo_bar'))->studly()->get());
    }

    /** @test */
    public function str_substr(): void
    {
        $this->assertSame(Str::substr('foobar', -1), (new StrProxy('foobar'))->substr(-1)->get());
    }

    /** @test */
    public function str_title(): void
    {
        $this->assertSame(
            Str::title('a nice title uses the correct case'),
            (new StrProxy('a nice title uses the correct case'))->title()->get()
        );
    }

    /** @test */
    public function str_uc_first(): void
    {
        $this->assertSame(Str::ucfirst('laravel'), (new StrProxy('laravel'))->ucfirst()->get());
        $this->assertSame(Str::ucfirst('laravel framework'), (new StrProxy('laravel framework'))->ucfirst()->get());
    }

    /** @test */
    public function str_upper(): void
    {
        $this->assertSame(Str::upper('foo bar baz'), (new StrProxy('foo bar baz'))->upper()->get());
        $this->assertSame(Str::upper('foO bAr BaZ'), (new StrProxy('foO bAr BaZ'))->upper()->get());
    }

    /** @test */
    public function str_uuid(): void
    {
        $pattern = '/\w{8}-\w{4}-\w{4}-\w{4}-\w{12}/';
        $this->assertRegExp($pattern, (string) Str::uuid());
        $this->assertRegExp($pattern, (string) (new StrProxy(''))->uuid());
    }

    /** @test */
    public function str_words(): void
    {
        $this->assertSame(
            Str::words('Perfectly balanced, as all things should be.', 3, ' >>>'),
            (new StrProxy('Perfectly balanced, as all things should be.'))->words(3, ' >>>')->get()
        );
    }

    /** @test */
    public function utterly_not_so_complex_example(): void
    {
        $this->assertSame(
            Str::title(Str::replaceArray('_', [' '], Str::snake('fooBar'))),
            (new StrProxy('fooBar'))->snake()->replaceArray('_', [' '])->title()->get()
        );
    }
}
