<?php

namespace Illuminate\Tests\Translation;

use Illuminate\Contracts\Translation\Loader;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Tests\Translation\Fixtures\Enums\Bar;
use Illuminate\Tests\Translation\Fixtures\Enums\Baz;
use Illuminate\Tests\Translation\Fixtures\Enums\Foo;
use Illuminate\Translation\MessageSelector;
use Illuminate\Translation\Translator;
use InvalidArgumentException;
use JMac\Testing\Double;
use JMac\Testing\Integrations\PHPUnit\VerifiesDoubles;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

class TranslationTranslatorTest extends TestCase
{
    use VerifiesDoubles;

    public function testSetLocaleRejectsPathTraversal()
    {
        $t = new Translator($this->getLoader(), 'en');

        foreach (['../secret', '..\\secret', '..', "en\0"] as $locale) {
            try {
                $t->setLocale($locale);

                $this->fail("Locale [{$locale}] should have been rejected.");
            } catch (InvalidArgumentException $e) {
                $this->assertSame('Invalid characters present in locale.', $e->getMessage());
            }
        }

        $this->assertSame('en', $t->getLocale());
    }

    public function testHasMethodReturnsFalseWhenReturnedTranslationIsNull()
    {
        $t = $this->getMockBuilder(Translator::class)->onlyMethods(['get'])->setConstructorArgs([$this->getLoader(), 'en'])->getMock();
        $t->expects($this->once())->method('get')->with('foo', [], 'bar')->willReturn('foo');
        $this->assertFalse($t->has('foo', 'bar'));

        $t = $this->getMockBuilder(Translator::class)->onlyMethods(['get'])->setConstructorArgs([$this->getLoader(), 'en', 'sp'])->getMock();
        $t->expects($this->once())->method('get')->with('foo', [], 'bar')->willReturn('bar');
        $this->assertTrue($t->has('foo', 'bar'));

        $t = $this->getMockBuilder(Translator::class)->onlyMethods(['get'])->setConstructorArgs([$this->getLoader(), 'en'])->getMock();
        $t->expects($this->once())->method('get')->with('foo', [], 'bar', false)->willReturn('bar');
        $this->assertTrue($t->hasForLocale('foo', 'bar'));

        $t = $this->getMockBuilder(Translator::class)->onlyMethods(['get'])->setConstructorArgs([$this->getLoader(), 'en'])->getMock();
        $t->expects($this->once())->method('get')->with('foo', [], 'bar', false)->willReturn('foo');
        $this->assertFalse($t->hasForLocale('foo', 'bar'));

        $t = new Translator($this->getLoader(), 'en');
        $t->getLoader()->expects('load')->with('en', '*', '*')->returns([]);
        $t->getLoader()->expects('load')->with('en', 'foo', '*')->returns(['foo' => 'bar']);
        $this->assertTrue($t->hasForLocale('foo'));

        $t = new Translator($this->getLoader(), 'en');
        $t->getLoader()->expects('load')->with('en', '*', '*')->returns([]);
        $t->getLoader()->expects('load')->with('en', 'foo', '*')->returns([]);
        $this->assertFalse($t->hasForLocale('foo'));
    }

    public function testGetMethodProperlyLoadsAndRetrievesItem()
    {
        $t = new Translator($this->getLoader(), 'en');
        $t->getLoader()->expects('load')->with('en', '*', '*')->returns([]);
        $t->getLoader()->expects('load')->with('en', 'bar', 'foo')->returns(['foo' => 'foo', 'baz' => 'breeze :foo', 'qux' => ['tree :foo', 'breeze :foo']]);
        $this->assertEquals(['tree bar', 'breeze bar'], $t->get('foo::bar.qux', ['foo' => 'bar'], 'en'));
        $this->assertSame('breeze bar', $t->get('foo::bar.baz', ['foo' => 'bar'], 'en'));
        $this->assertSame('foo', $t->get('foo::bar.foo'));
    }

    public function testGetMethodProperlyLoadsAndRetrievesArrayItem()
    {
        $t = new Translator($this->getLoader(), 'en');
        $t->getLoader()->expects('load')->with('en', '*', '*')->returns([]);
        $t->getLoader()->expects('load')->with('en', 'bar', 'foo')->returns(['foo' => 'foo', 'baz' => 'breeze :foo', 'qux' => ['tree :foo', 'breeze :foo', 'beep' => ['rock' => 'tree :foo']]]);
        $this->assertEquals(['foo' => 'foo', 'baz' => 'breeze bar', 'qux' => ['tree bar', 'breeze bar', 'beep' => ['rock' => 'tree bar']]], $t->get('foo::bar', ['foo' => 'bar'], 'en'));
        $this->assertSame('breeze bar', $t->get('foo::bar.baz', ['foo' => 'bar'], 'en'));
        $this->assertSame('foo', $t->get('foo::bar.foo'));
    }

    public function testStringMethodProperlyLoadsAndRetrievesStringItem()
    {
        $t = new Translator($this->getLoader(), 'en');
        $t->getLoader()->expects('load')->with('en', '*', '*')->returns([]);
        $t->getLoader()->expects('load')->with('en', 'bar', 'foo')->returns(['baz' => 'breeze :foo']);
        $this->assertSame('breeze bar', $t->string('foo::bar.baz', ['foo' => 'bar'], 'en'));
    }

    public function testStringMethodThrowsExceptionForArrayItem()
    {
        $t = new Translator($this->getLoader(), 'en');
        $t->getLoader()->expects('load')->with('en', '*', '*')->returns([]);
        $t->getLoader()->expects('load')->with('en', 'bar', 'foo')->returns(['baz' => ['breeze']]);
        $this->expectExceptionObject(new InvalidArgumentException('Translation value for key [foo::bar.baz] must be a string, array given.'));

        $t->string('foo::bar.baz', [], 'en');
    }

    public function testArrayMethodProperlyLoadsAndRetrievesArrayItem()
    {
        $t = new Translator($this->getLoader(), 'en');
        $t->getLoader()->expects('load')->with('en', '*', '*')->returns([]);
        $t->getLoader()->expects('load')->with('en', 'bar', 'foo')->returns(['baz' => ['breeze :foo']]);
        $this->assertSame(['breeze bar'], $t->array('foo::bar.baz', ['foo' => 'bar'], 'en'));
    }

    public function testArrayMethodThrowsExceptionForStringItem()
    {
        $t = new Translator($this->getLoader(), 'en');
        $t->getLoader()->expects('load')->with('en', '*', '*')->returns([]);
        $t->getLoader()->expects('load')->with('en', 'bar', 'foo')->returns(['baz' => 'breeze']);
        $this->expectExceptionObject(new InvalidArgumentException('Translation value for key [foo::bar.baz] must be an array, string given.'));

        $t->array('foo::bar.baz', [], 'en');
    }

    public function testGetMethodForNonExistingReturnsSameKey()
    {
        $t = new Translator($this->getLoader(), 'en');
        $t->getLoader()->expects('load')->with('en', '*', '*')->returns([]);
        $t->getLoader()->expects('load')->with('en', 'bar', 'foo')->returns(['foo' => 'foo', 'baz' => 'breeze :foo', 'qux' => ['tree :foo', 'breeze :foo']]);
        $t->getLoader()->expects('load')->with('en', 'unknown', 'foo')->returns([]);
        $this->assertSame('foo::unknown', $t->get('foo::unknown', ['foo' => 'bar'], 'en'));
        $this->assertSame('foo::bar.unknown', $t->get('foo::bar.unknown', ['foo' => 'bar'], 'en'));
        $this->assertSame('foo::unknown.bar', $t->get('foo::unknown.bar'));
    }

    public function testTransMethodProperlyLoadsAndRetrievesItemWithHTMLInTheMessage()
    {
        $t = new Translator($this->getLoader(), 'en');
        $t->getLoader()->expects('load')->with('en', '*', '*')->returns([]);
        $t->getLoader()->expects('load')->with('en', 'foo', '*')->returns(['bar' => 'breeze <p>test</p>']);
        $this->assertSame('breeze <p>test</p>', $t->get('foo.bar', [], 'en'));
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testGetMethodProperlyLoadsAndRetrievesItemWithCapitalization()
    {
        $t = $this->getMockBuilder(Translator::class)->onlyMethods([])->setConstructorArgs([$this->getLoader(), 'en'])->getMock();
        $t->getLoader()->expects('load')->with('en', '*', '*')->returns([]);
        $t->getLoader()->expects('load')->with('en', 'bar', 'foo')->returns(['foo' => 'foo', 'baz' => 'breeze :0 :Foo :BAR']);
        $this->assertSame('breeze john Bar FOO', $t->get('foo::bar.baz', ['john', 'foo' => 'bar', 'bar' => 'foo'], 'en'));
        $this->assertSame('foo', $t->get('foo::bar.foo'));
    }

    public function testGetMethodProperlyLoadsAndRetrievesItemWithLongestReplacementsFirst()
    {
        $t = new Translator($this->getLoader(), 'en');
        $t->getLoader()->expects('load')->with('en', '*', '*')->returns([]);
        $t->getLoader()->expects('load')->with('en', 'bar', 'foo')->returns(['foo' => 'foo', 'baz' => 'breeze :foo :foobar']);
        $this->assertSame('breeze bar taylor', $t->get('foo::bar.baz', ['foo' => 'bar', 'foobar' => 'taylor'], 'en'));
        $this->assertSame('breeze foo bar baz taylor', $t->get('foo::bar.baz', ['foo' => 'foo bar baz', 'foobar' => 'taylor'], 'en'));
        $this->assertSame('foo', $t->get('foo::bar.foo'));
    }

    public function testGetMethodProperlyLoadsAndRetrievesItemForFallback()
    {
        $t = new Translator($this->getLoader(), 'en');
        $t->setFallback('lv');
        $t->getLoader()->expects('load')->with('en', '*', '*')->returns([]);
        $t->getLoader()->expects('load')->with('en', 'bar', 'foo')->returns([]);
        $t->getLoader()->expects('load')->with('lv', 'bar', 'foo')->returns(['foo' => 'foo', 'baz' => 'breeze :foo']);
        $this->assertSame('breeze bar', $t->get('foo::bar.baz', ['foo' => 'bar'], 'en'));
        $this->assertSame('foo', $t->get('foo::bar.foo'));
    }

    public function testGetDoesNotCallGetLineTwiceForMissingKeyWhenLocaleMatchesFallback()
    {
        $t = $this->getMockBuilder(Translator::class)->onlyMethods(['getLine'])->setConstructorArgs([$this->getLoader(), 'en'])->getMock();
        $t->setFallback('en');
        $t->getLoader()->expects('load')->with('en', '*', '*')->returns([]);

        $t->expects($this->once())->method('getLine')->with('*', 'messages', 'en', 'test', [])->willReturn(null);

        $t->get('messages.test', [], 'en');
    }

    public function testGetMethodProperlyLoadsAndRetrievesItemForGlobalNamespace()
    {
        $t = new Translator($this->getLoader(), 'en');
        $t->getLoader()->expects('load')->with('en', '*', '*')->returns([]);
        $t->getLoader()->expects('load')->with('en', 'foo', '*')->returns(['bar' => 'breeze :foo']);
        $this->assertSame('breeze bar', $t->get('foo.bar', ['foo' => 'bar']));
    }

    public function testChoiceMethodProperlyLoadsAndRetrievesItemForAnInt()
    {
        $t = $this->getMockBuilder(Translator::class)->onlyMethods(['get', 'localeForChoice'])->setConstructorArgs([$this->getLoader(), 'en'])->getMock();
        $t->expects($this->once())->method('get')->with('foo', [], 'en')->willReturn('line');
        $t->expects($this->once())->method('localeForChoice')->with('foo', null)->willReturn('en');
        $selector = Double::for(MessageSelector::class);
        $selector->expects('choose')->with('line', 10, 'en')->returns('choiced');
        $t->setSelector($selector);

        $t->choice('foo', 10, ['replace']);
    }

    public function testChoiceMethodProperlyLoadsAndRetrievesItemForAFloat()
    {
        $t = $this->getMockBuilder(Translator::class)->onlyMethods(['get', 'localeForChoice'])->setConstructorArgs([$this->getLoader(), 'en'])->getMock();
        $t->expects($this->once())->method('get')->with('foo', [], 'en')->willReturn('line');
        $t->expects($this->once())->method('localeForChoice')->with('foo', null)->willReturn('en');
        $selector = Double::for(MessageSelector::class);
        $selector->expects('choose')->with('line', 1.2, 'en')->returns('choiced');
        $t->setSelector($selector);

        $t->choice('foo', 1.2, ['replace']);
    }

    public function testChoiceMethodProperlyCountsCollectionsAndLoadsAndRetrievesItem()
    {
        $selector = Double::for(MessageSelector::class);
        $selector->expects('choose')->times(2)->with('line', 3, 'en')->returns('choiced');
        $t = $this->getMockBuilder(Translator::class)->onlyMethods(['get', 'localeForChoice'])->setConstructorArgs([$this->getLoader(), 'en'])->getMock();
        $t->expects($this->exactly(2))->method('get')->with('foo', [], 'en')->willReturn('line');
        $t->expects($this->exactly(2))->method('localeForChoice')->with('foo', null)->willReturn('en');
        $t->setSelector($selector);

        $values = ['foo', 'bar', 'baz'];
        $t->choice('foo', $values, ['replace']);

        $values = new Collection(['foo', 'bar', 'baz']);
        $t->choice('foo', $values, ['replace']);
    }

    public function testChoiceMethodProperlySelectsLocaleForChoose()
    {
        $t = $this->getMockBuilder(Translator::class)->onlyMethods(['get', 'hasForLocale'])->setConstructorArgs([$this->getLoader(), 'cs'])->getMock();
        $t->setFallback('en');
        $t->expects($this->once())->method('get')->with('foo', [], 'en')->willReturn('line');
        $t->expects($this->once())->method('hasForLocale')->with('foo', 'cs')->willReturn(false);
        $selector = Double::for(MessageSelector::class);
        $selector->expects('choose')->with('line', 10, 'en')->returns('choiced');
        $t->setSelector($selector);

        $t->choice('foo', 10, ['replace']);
    }

    public function testChoiceMethodProperlyUsesCustomCountReplacement()
    {
        $t = $this->getMockBuilder(Translator::class)->onlyMethods(['get', 'localeForChoice'])->setConstructorArgs([$this->getLoader(), 'en'])->getMock();
        $t->expects($this->once())->method('get')->with(':count foos', [], 'en')->willReturn('{1} :count foos|[2,*] :count foos');
        $t->expects($this->once())->method('localeForChoice')->with(':count foos', null)->willReturn('en');
        $selector = Double::for(MessageSelector::class);
        $selector->expects('choose')->with('{1} :count foos|[2,*] :count foos', 1234, 'en')->returns(':count foos');
        $t->setSelector($selector);

        $this->assertSame('1,234 foos', $t->choice(':count foos', 1234, ['count' => '1,234']));
    }

    public function testGetJson()
    {
        $t = new Translator($this->getLoader(), 'en');
        $t->getLoader()->expects('load')->with('en', '*', '*')->returns(['foo' => 'one']);
        $this->assertSame('one', $t->get('foo'));
    }

    public function testGetJsonReplaces()
    {
        $t = new Translator($this->getLoader(), 'en');
        $t->getLoader()->expects('load')->with('en', '*', '*')->returns(['foo :i:c :u' => 'bar :i:c :u']);
        $this->assertSame('bar onetwo three', $t->get('foo :i:c :u', ['i' => 'one', 'c' => 'two', 'u' => 'three']));
    }

    public function testGetJsonHasAtomicReplacements()
    {
        $t = new Translator($this->getLoader(), 'en');
        $t->getLoader()->expects('load')->with('en', '*', '*')->returns(['Hello :foo!' => 'Hello :foo!']);
        $this->assertSame('Hello baz:bar!', $t->get('Hello :foo!', ['foo' => 'baz:bar', 'bar' => 'abcdef']));
    }

    public function testGetJsonReplacesForAssociativeInput()
    {
        $t = new Translator($this->getLoader(), 'en');
        $t->getLoader()->expects('load')->with('en', '*', '*')->returns(['foo :i :c' => 'bar :i :c']);
        $this->assertSame('bar eye see', $t->get('foo :i :c', ['i' => 'eye', 'c' => 'see']));
    }

    public function testGetJsonPreservesOrder()
    {
        $t = new Translator($this->getLoader(), 'en');
        $t->getLoader()->expects('load')->with('en', '*', '*')->returns(['to :name I give :greeting' => ':greeting :name']);
        $this->assertSame('Greetings David', $t->get('to :name I give :greeting', ['name' => 'David', 'greeting' => 'Greetings']));
    }

    public function testGetJsonForNonExistingJsonKeyLooksForRegularKeys()
    {
        $t = new Translator($this->getLoader(), 'en');
        $t->getLoader()->expects('load')->with('en', '*', '*')->returns([]);
        $t->getLoader()->expects('load')->with('en', 'foo', '*')->returns(['bar' => 'one']);
        $this->assertSame('one', $t->get('foo.bar'));
    }

    public function testGetJsonForNonExistingJsonKeyLooksForRegularKeysAndReplace()
    {
        $t = new Translator($this->getLoader(), 'en');
        $t->getLoader()->expects('load')->with('en', '*', '*')->returns([]);
        $t->getLoader()->expects('load')->with('en', 'foo', '*')->returns(['bar' => 'one :message']);
        $this->assertSame('one two', $t->get('foo.bar', ['message' => 'two']));
    }

    public function testGetJsonForNonExistingReturnsSameKey()
    {
        $t = new Translator($this->getLoader(), 'en');
        $t->getLoader()->expects('load')->with('en', '*', '*')->returns([]);
        $t->getLoader()->expects('load')->with('en', 'Foo that bar', '*')->returns([]);
        $this->assertSame('Foo that bar', $t->get('Foo that bar'));
    }

    public function testGetJsonForNonExistingReturnsSameKeyAndReplaces()
    {
        $t = new Translator($this->getLoader(), 'en');
        $t->getLoader()->expects('load')->with('en', '*', '*')->returns([]);
        $t->getLoader()->expects('load')->with('en', 'foo :message', '*')->returns([]);
        $this->assertSame('foo baz', $t->get('foo :message', ['message' => 'baz']));
    }

    public function testEmptyFallbacks()
    {
        $t = new Translator($this->getLoader(), 'en');
        $t->getLoader()->expects('load')->with('en', '*', '*')->returns([]);
        $t->getLoader()->expects('load')->with('en', 'foo :message', '*')->returns([]);
        $this->assertSame('foo ', $t->get('foo :message', ['message' => null]));
    }

    public function testGetJsonReplacesWithStringable()
    {
        $t = new Translator($this->getLoader(), 'en');
        $t->getLoader()->expects('load')->with('en', '*', '*')->returns(['test' => 'the date is :date']);

        $date = Carbon::createFromTimestamp(0);

        $this->assertSame(
            'the date is 1970-01-01 00:00:00',
            $t->get('test', ['date' => $date])
        );

        $t->stringable(function (\Illuminate\Support\Carbon $carbon) {
            return $carbon->format('jS M Y');
        });
        $this->assertSame(
            'the date is 1st Jan 1970',
            $t->get('test', ['date' => $date])
        );
    }

    public function testGetJsonReplacesWithEnums()
    {
        $t = new Translator($this->getLoader(), 'en');
        $t->getLoader()->expects('load')->with('en', '*', '*')->returns([
            'string_backed_enum' => 'Laravel 12 was released in :month 2025',
            'int_backed_enum' => 'Stay tuned for Laravel v:version',
            'unit_enum' => ':person gets excited about every new Laravel release',
        ]);

        $this->assertSame(
            'Laravel 12 was released in February 2025',
            $t->get('string_backed_enum', ['month' => Baz::February])
        );

        $this->assertSame(
            'Stay tuned for Laravel v13',
            $t->get('int_backed_enum', ['version' => Bar::Thirteen])
        );

        $this->assertSame(
            'Hosni gets excited about every new Laravel release',
            $t->get('unit_enum', ['person' => Foo::Hosni])
        );
    }

    public function testTagReplacements()
    {
        $t = new Translator($this->getLoader(), 'en');

        $t->getLoader()->expects('load')->with('en', '*', '*')->returns([]);
        $t->getLoader()->expects('load')->with('en', 'We have some nice <docs-link>documentation</docs-link>', '*')->returns([]);

        $this->assertSame(
            'We have some nice <a href="https://laravel.com/docs">documentation</a>',
            $t->get(
                'We have some nice <docs-link>documentation</docs-link>',
                [
                    'docs-link' => fn ($children) => "<a href=\"https://laravel.com/docs\">$children</a>",
                ]
            )
        );
    }

    public function testTagReplacementsHandleMultipleOfSameTag()
    {
        $t = new Translator($this->getLoader(), 'en');

        $t->getLoader()->expects('load')->with('en', '*', '*')->returns([]);
        $t->getLoader()->expects('load')->with('en', '<bold-this>bold</bold-this> something else <bold-this>also bold</bold-this>', '*')->returns([]);

        $this->assertSame(
            '<b>bold</b> something else <b>also bold</b>',
            $t->get(
                '<bold-this>bold</bold-this> something else <bold-this>also bold</bold-this>',
                [
                    'bold-this' => fn ($children) => "<b>$children</b>",
                ]
            )
        );
    }

    public function testDetermineLocalesUsingMethod()
    {
        $t = new Translator($this->getLoader(), 'en');
        $t->determineLocalesUsing(function ($locales) {
            $this->assertSame(['en'], $locales);

            return ['en', 'lz'];
        });
        $t->getLoader()->expects('load')->with('en', '*', '*')->returns([]);
        $t->getLoader()->expects('load')->with('en', 'foo', '*')->returns([]);
        $t->getLoader()->expects('load')->with('lz', 'foo', '*')->returns([]);
        $this->assertSame('foo', $t->get('foo'));
    }

    protected function getLoader()
    {
        return Double::for(Loader::class);
    }
}
