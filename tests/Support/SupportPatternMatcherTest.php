<?php

namespace Illuminate\Tests\Support;

use Illuminate\Support\PatternMatcher;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class SupportPatternMatcherTest extends TestCase
{
    public function testIs()
    {
        $this->assertTrue(PatternMatcher::is('/', '/'));
        $this->assertFalse(PatternMatcher::is('/', ' /'));
        $this->assertFalse(PatternMatcher::is('/', '/a'));
        $this->assertTrue(PatternMatcher::is('foo/*', 'foo/bar/baz'));

        $this->assertTrue(PatternMatcher::is('*@*', 'App\Class@method'));
        $this->assertTrue(PatternMatcher::is('*@*', 'app\Class@'));
        $this->assertTrue(PatternMatcher::is('*@*', '@method'));

        // is case sensitive
        $this->assertFalse(PatternMatcher::is('*BAZ*', 'foo/bar/baz'));
        $this->assertFalse(PatternMatcher::is('*FOO*', 'foo/bar/baz'));
        $this->assertFalse(PatternMatcher::is('A', 'a'));

        // Accepts array of patterns
        $this->assertTrue(PatternMatcher::is(['a*', 'b*'], 'a/'));
        $this->assertTrue(PatternMatcher::is(['a*', 'b*'], 'b/'));
        $this->assertFalse(PatternMatcher::is(['a*', 'b*'], 'f/'));

        // numeric values and patterns
        $this->assertFalse(PatternMatcher::is(['a*', 'b*'], 123));
        $this->assertTrue(PatternMatcher::is(['*2*', 'b*'], 11211));

        $this->assertTrue(PatternMatcher::is('*/foo', 'blah/baz/foo'));

        $valueObject = new StringableObjectStub('foo/bar/baz');
        $patternObject = new StringableObjectStub('foo/*');

        $this->assertTrue(PatternMatcher::is('foo/bar/baz', $valueObject));
        $this->assertTrue(PatternMatcher::is($patternObject, $valueObject));

        // empty patterns
        $this->assertFalse(PatternMatcher::is([], 'test'));

        $this->assertFalse(PatternMatcher::is('', 0));
        $this->assertFalse(PatternMatcher::is([null], 0));
        $this->assertTrue(PatternMatcher::is([null], null));
    }

    public function testIsWithMultilineStrings()
    {
        $this->assertFalse(PatternMatcher::is('/', "/\n"));
        $this->assertTrue(PatternMatcher::is('/*', "/\n"));
        $this->assertTrue(PatternMatcher::is('*/*', "/\n"));
        $this->assertTrue(PatternMatcher::is('*/*', "\n/\n"));

        $this->assertTrue(PatternMatcher::is('*', "\n"));
        $this->assertTrue(PatternMatcher::is('*', "\n\n"));
        $this->assertFalse(PatternMatcher::is('', "\n"));
        $this->assertFalse(PatternMatcher::is('', "\n\n"));

        $multilineValue = <<<'VALUE'
        <?php

        namespace Illuminate\Tests\Support;

        use Exception;
        VALUE;

        $this->assertTrue(PatternMatcher::is($multilineValue, $multilineValue));
        $this->assertTrue(PatternMatcher::is('*', $multilineValue));
        $this->assertTrue(PatternMatcher::is("*namespace Illuminate\Tests\*", $multilineValue));
        $this->assertFalse(PatternMatcher::is("namespace Illuminate\Tests\*", $multilineValue));
        $this->assertFalse(PatternMatcher::is("*namespace Illuminate\Tests", $multilineValue));
        $this->assertTrue(PatternMatcher::is('<?php*', $multilineValue));
        $this->assertTrue(PatternMatcher::is("<?php*namespace Illuminate\Tests\*", $multilineValue));
        $this->assertFalse(PatternMatcher::is('use Exception;', $multilineValue));
        $this->assertFalse(PatternMatcher::is('use Exception;*', $multilineValue));
        $this->assertTrue(PatternMatcher::is('*use Exception;', $multilineValue));

        $this->assertTrue(PatternMatcher::is("<?php\n\nnamespace Illuminate\Tests\*", $multilineValue));

        $this->assertTrue(PatternMatcher::is(<<<'PATTERN'
        <?php
        *
        namespace Illuminate\Tests\*
        PATTERN, $multilineValue));

        $this->assertTrue(PatternMatcher::is(<<<'PATTERN'
        <?php

        namespace Illuminate\Tests\*
        PATTERN, $multilineValue));
    }

    public function testIsUrl()
    {
        $this->assertTrue(PatternMatcher::isUrl('https://laravel.com'));
        $this->assertFalse(PatternMatcher::isUrl('invalid url'));
    }

    #[DataProvider('validUuidList')]
    public function testIsUuidWithValidUuid($uuid)
    {
        $this->assertTrue(PatternMatcher::isUuid($uuid));
    }

    #[DataProvider('invalidUuidList')]
    public function testIsUuidWithInvalidUuid($uuid)
    {
        $this->assertFalse(PatternMatcher::isUuid($uuid));
    }

    public static function validUuidList()
    {
        return [
            ['a0a2a2d2-0b87-4a18-83f2-2529882be2de'],
            ['145a1e72-d11d-11e8-a8d5-f2801f1b9fd1'],
            ['00000000-0000-0000-0000-000000000000'],
            ['e60d3f48-95d7-4d8d-aad0-856f29a27da2'],
            ['ff6f8cb0-c57d-11e1-9b21-0800200c9a66'],
            ['ff6f8cb0-c57d-21e1-9b21-0800200c9a66'],
            ['ff6f8cb0-c57d-31e1-9b21-0800200c9a66'],
            ['ff6f8cb0-c57d-41e1-9b21-0800200c9a66'],
            ['ff6f8cb0-c57d-51e1-9b21-0800200c9a66'],
            ['FF6F8CB0-C57D-11E1-9B21-0800200C9A66'],
        ];
    }

    public static function invalidUuidList()
    {
        return [
            ['not a valid uuid so we can test this'],
            ['zf6f8cb0-c57d-11e1-9b21-0800200c9a66'],
            ['145a1e72-d11d-11e8-a8d5-f2801f1b9fd1'.PHP_EOL],
            ['145a1e72-d11d-11e8-a8d5-f2801f1b9fd1 '],
            [' 145a1e72-d11d-11e8-a8d5-f2801f1b9fd1'],
            ['145a1e72-d11d-11e8-a8d5-f2z01f1b9fd1'],
            ['3f6f8cb0-c57d-11e1-9b21-0800200c9a6'],
            ['af6f8cb-c57d-11e1-9b21-0800200c9a66'],
            ['af6f8cb0c57d11e19b210800200c9a66'],
            ['ff6f8cb0-c57da-51e1-9b21-0800200c9a66'],
        ];
    }

    public function testMatch(): void
    {
        $this->assertSame('bar', PatternMatcher::match('/bar/', 'foo bar'));
        $this->assertSame('bar', PatternMatcher::match('/foo (.*)/', 'foo bar'));
        $this->assertEmpty(PatternMatcher::match('/nothing/', 'foo bar'));

        $this->assertEquals(['bar', 'bar'], PatternMatcher::matchAll('/bar/', 'bar foo bar')->all());

        $this->assertEquals(['un', 'ly'], PatternMatcher::matchAll('/f(\w*)/', 'bar fun bar fly')->all());
        $this->assertEmpty(PatternMatcher::matchAll('/nothing/', 'bar fun bar fly'));

        $this->assertEmpty(PatternMatcher::match('/pattern/', ''));
        $this->assertEmpty(PatternMatcher::matchAll('/pattern/', ''));
    }

    public function testIsMatch()
    {
        $this->assertTrue(PatternMatcher::isMatch('/.*,.*!/', 'Hello, Laravel!'));
        $this->assertTrue(PatternMatcher::isMatch('/^.*$(.*)/', 'Hello, Laravel!'));
        $this->assertTrue(PatternMatcher::isMatch('/laravel/i', 'Hello, Laravel!'));
        $this->assertTrue(PatternMatcher::isMatch('/^(.*(.*(.*)))/', 'Hello, Laravel!'));

        $this->assertFalse(PatternMatcher::isMatch('/H.o/', 'Hello, Laravel!'));
        $this->assertFalse(PatternMatcher::isMatch('/^laravel!/i', 'Hello, Laravel!'));
        $this->assertFalse(PatternMatcher::isMatch('/laravel!(.*)/', 'Hello, Laravel!'));
        $this->assertFalse(PatternMatcher::isMatch('/^[a-zA-Z,!]+$/', 'Hello, Laravel!'));

        $this->assertTrue(PatternMatcher::isMatch(['/.*,.*!/', '/H.o/'], 'Hello, Laravel!'));
        $this->assertTrue(PatternMatcher::isMatch(['/^laravel!/i', '/^.*$(.*)/'], 'Hello, Laravel!'));
        $this->assertTrue(PatternMatcher::isMatch(['/laravel/i', '/laravel!(.*)/'], 'Hello, Laravel!'));
        $this->assertTrue(PatternMatcher::isMatch(['/^[a-zA-Z,!]+$/', '/^(.*(.*(.*)))/'], 'Hello, Laravel!'));
    }
}