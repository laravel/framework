<?php

namespace Illuminate\Tests\Support;

use Illuminate\Container\Container;
use Illuminate\Encryption\Encrypter;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Stringable;
use Illuminate\Support\Uri;
use League\CommonMark\Environment\EnvironmentBuilderInterface;
use League\CommonMark\Extension\ExtensionInterface;
use PHPUnit\Framework\TestCase;

class SupportStringableTest extends TestCase
{
    protected Container $container;

    /**
     * @param  string  $string
     * @return \Illuminate\Support\Stringable
     */
    protected function stringable($string = '')
    {
        return new Stringable($string);
    }

    public function testClassBasename()
    {
        $this->assertEquals(
            class_basename(static::class),
            $this->stringable(static::class)->classBasename()
        );
    }

    public function testIsAscii()
    {
        $this->assertTrue($this->stringable('A')->isAscii());
        $this->assertFalse($this->stringable('ù')->isAscii());
    }

    public function testIsUrl()
    {
        $this->assertTrue($this->stringable('https://laravel.com')->isUrl());
        $this->assertTrue($this->stringable('https://laravel.com')->isUrl(['https']));

        $this->assertFalse($this->stringable('invalid url')->isUrl());
        $this->assertFalse($this->stringable('https://laravel.com')->isUrl(['http']));
    }

    public function testIsUuid()
    {
        $this->assertTrue($this->stringable('2cdc7039-65a6-4ac7-8e5d-d554a98e7b15')->isUuid());
        $this->assertTrue($this->stringable('2cdc7039-65a6-4ac7-8e5d-d554a98e7b15')->isUuid(4));

        $this->assertFalse($this->stringable('2cdc7039-65a6-4ac7-8e5d-d554a98')->isUuid());
        $this->assertFalse($this->stringable('2cdc7039-65a6-4ac7-8e5d-d554a98e7b15')->isUuid(7));
    }

    public function testIsUlid()
    {
        $this->assertTrue($this->stringable('01GJSNW9MAF792C0XYY8RX6QFT')->isUlid());
        $this->assertFalse($this->stringable('01GJSNW9MAF-792C0XYY8RX6ssssss-QFT')->isUlid());
    }

    public function testIsJson()
    {
        $this->assertTrue($this->stringable('1')->isJson());
        $this->assertTrue($this->stringable('[1,2,3]')->isJson());
        $this->assertTrue($this->stringable('[1,   2,   3]')->isJson());
        $this->assertTrue($this->stringable('{"first": "John", "last": "Doe"}')->isJson());
        $this->assertTrue($this->stringable('[{"first": "John", "last": "Doe"}, {"first": "Jane", "last": "Doe"}]')->isJson());

        $this->assertFalse($this->stringable('1,')->isJson());
        $this->assertFalse($this->stringable('[1,2,3')->isJson());
        $this->assertFalse($this->stringable('[1,   2   3]')->isJson());
        $this->assertFalse($this->stringable('{first: "John"}')->isJson());
        $this->assertFalse($this->stringable('[{first: "John"}, {first: "Jane"}]')->isJson());
        $this->assertFalse($this->stringable('')->isJson());
        $this->assertFalse($this->stringable(null)->isJson());
    }

    public function testIsMatch()
    {
        $this->assertTrue($this->stringable('Hello, Laravel!')->isMatch('/.*,.*!/'));
        $this->assertTrue($this->stringable('Hello, Laravel!')->isMatch('/^.*$(.*)/'));
        $this->assertTrue($this->stringable('Hello, Laravel!')->isMatch('/laravel/i'));
        $this->assertTrue($this->stringable('Hello, Laravel!')->isMatch('/^(.*(.*(.*)))/'));

        $this->assertFalse($this->stringable('Hello, Laravel!')->isMatch('/H.o/'));
        $this->assertFalse($this->stringable('Hello, Laravel!')->isMatch('/^laravel!/i'));
        $this->assertFalse($this->stringable('Hello, Laravel!')->isMatch('/laravel!(.*)/'));
        $this->assertFalse($this->stringable('Hello, Laravel!')->isMatch('/^[a-zA-Z,!]+$/'));

        $this->assertTrue($this->stringable('Hello, Laravel!')->isMatch(['/.*,.*!/', '/H.o/']));
        $this->assertTrue($this->stringable('Hello, Laravel!')->isMatch(['/^laravel!/i', '/^.*$(.*)/']));
        $this->assertTrue($this->stringable('Hello, Laravel!')->isMatch(['/laravel/i', '/laravel!(.*)/']));
        $this->assertTrue($this->stringable('Hello, Laravel!')->isMatch(['/^[a-zA-Z,!]+$/', '/^(.*(.*(.*)))/']));
    }

    public function testIsEmpty()
    {
        $this->assertTrue($this->stringable('')->isEmpty());
        $this->assertFalse($this->stringable('A')->isEmpty());
        $this->assertFalse($this->stringable('0')->isEmpty());
    }

    public function testIsNotEmpty()
    {
        $this->assertFalse($this->stringable('')->isNotEmpty());
        $this->assertTrue($this->stringable('A')->isNotEmpty());
    }

    public function testPluralStudly()
    {
        $this->assertSame('LaraCon', (string) $this->stringable('LaraCon')->pluralStudly(1));
        $this->assertSame('LaraCons', (string) $this->stringable('LaraCon')->pluralStudly(2));
        $this->assertSame('LaraCon', (string) $this->stringable('LaraCon')->pluralStudly(-1));
        $this->assertSame('LaraCons', (string) $this->stringable('LaraCon')->pluralStudly(-2));
    }

    public function testPluralPascal()
    {
        $this->assertSame('LaraCons', (string) $this->stringable('LaraCon')->pluralPascal(2));
        $this->assertSame('LaraCon', (string) $this->stringable('LaraCon')->pluralPascal(1));
        $this->assertSame('LaraCons', (string) $this->stringable('LaraCon')->pluralPascal(-2));
        $this->assertSame('LaraCon', (string) $this->stringable('LaraCon')->pluralPascal(-1));
    }

    public function testMatch()
    {
        $stringable = $this->stringable('foo bar');

        $this->assertSame('bar', (string) $stringable->match('/bar/'));
        $this->assertSame('bar', (string) $stringable->match('/foo (.*)/'));
        $this->assertTrue($stringable->match('/nothing/')->isEmpty());

        $this->assertEquals(['bar', 'bar'], $this->stringable('bar foo bar')->matchAll('/bar/')->all());

        $stringable = $this->stringable('bar fun bar fly');

        $this->assertEquals(['un', 'ly'], $stringable->matchAll('/f(\w*)/')->all());
        $this->assertTrue($stringable->matchAll('/nothing/')->isEmpty());
    }

    public function testTake()
    {
        $this->assertSame('ab', (string) $this->stringable('abcdef')->take(2));
        $this->assertSame('ef', (string) $this->stringable('abcdef')->take(-2));
    }

    public function testTest()
    {
        $stringable = $this->stringable('foo bar');

        $this->assertTrue($stringable->test('/bar/'));
        $this->assertTrue($stringable->test('/foo (.*)/'));
    }

    public function testTrim()
    {
        $this->assertSame('foo', (string) $this->stringable(' foo ')->trim());
    }

    public function testLtrim()
    {
        $this->assertSame('foo ', (string) $this->stringable(' foo ')->ltrim());
    }

    public function testRtrim()
    {
        $this->assertSame(' foo', (string) $this->stringable(' foo ')->rtrim());
    }

    public function testCanBeLimitedByWords()
    {
        $this->assertSame('Taylor...', (string) $this->stringable('Taylor Otwell')->words(1));
        $this->assertSame('Taylor___', (string) $this->stringable('Taylor Otwell')->words(1, '___'));
        $this->assertSame('Taylor Otwell', (string) $this->stringable('Taylor Otwell')->words(3));
    }

    public function testUnless()
    {
        $this->assertSame('unless false', (string) $this->stringable('unless')->unless(false, function ($stringable, $value) {
            return $stringable->append(' false');
        }));

        $this->assertSame('unless true fallbacks to default', (string) $this->stringable('unless')->unless(true, function ($stringable, $value) {
            return $stringable->append($value);
        }, function ($stringable) {
            return $stringable->append(' true fallbacks to default');
        }));
    }

    public function testWhenContains()
    {
        $this->assertSame('Tony Stark', (string) $this->stringable('stark')->whenContains('tar', function ($stringable) {
            return $stringable->prepend('Tony ')->title();
        }, function ($stringable) {
            return $stringable->prepend('Arno ')->title();
        }));

        $this->assertSame('stark', (string) $this->stringable('stark')->whenContains('xxx', function ($stringable) {
            return $stringable->prepend('Tony ')->title();
        }));

        $this->assertSame('Arno Stark', (string) $this->stringable('stark')->whenContains('xxx', function ($stringable) {
            return $stringable->prepend('Tony ')->title();
        }, function ($stringable) {
            return $stringable->prepend('Arno ')->title();
        }));
    }

    public function testWhenContainsAll()
    {
        $this->assertSame('Tony Stark', (string) $this->stringable('tony stark')->whenContainsAll(['tony', 'stark'], function ($stringable) {
            return $stringable->title();
        }, function ($stringable) {
            return $stringable->studly();
        }));

        $this->assertSame('tony stark', (string) $this->stringable('tony stark')->whenContainsAll(['xxx'], function ($stringable) {
            return $stringable->title();
        }));

        $this->assertSame('TonyStark', (string) $this->stringable('tony stark')->whenContainsAll(['tony', 'xxx'], function ($stringable) {
            return $stringable->title();
        }, function ($stringable) {
            return $stringable->studly();
        }));
    }

    public function testDedup()
    {
        $this->assertSame(' laravel php framework ', (string) $this->stringable(' laravel   php  framework ')->deduplicate());
        $this->assertSame('what', (string) $this->stringable('whaaat')->deduplicate('a'));
        $this->assertSame('/some/odd/path/', (string) $this->stringable('/some//odd//path/')->deduplicate('/'));
        $this->assertSame('ムだム', (string) $this->stringable('ムだだム')->deduplicate('だ'));
    }

    public function testDirname()
    {
        $this->assertSame('/framework/tests', (string) $this->stringable('/framework/tests/Support')->dirname());
        $this->assertSame('/framework', (string) $this->stringable('/framework/tests/Support')->dirname(2));
        $this->assertSame('.', (string) $this->stringable('framework')->dirname());

        $this->assertSame('.', (string) $this->stringable('.')->dirname());

        $this->assertSame(DIRECTORY_SEPARATOR, (string) $this->stringable('/framework/')->dirname());
        $this->assertSame(DIRECTORY_SEPARATOR, (string) $this->stringable('/')->dirname());
    }

    public function testUcsplitOnStringable()
    {
        $this->assertSame(['Taylor', 'Otwell'], $this->stringable('TaylorOtwell')->ucsplit()->toArray());
        $this->assertSame(['Hello', 'From', 'Laravel'], $this->stringable('HelloFromLaravel')->ucsplit()->toArray());
        $this->assertSame(['He_llo_', 'World'], $this->stringable('He_llo_World')->ucsplit()->toArray());
    }

    public function testWhenEndsWith()
    {
        $this->assertSame('Tony Stark', (string) $this->stringable('tony stark')->whenEndsWith('ark', function ($stringable) {
            return $stringable->title();
        }, function ($stringable) {
            return $stringable->studly();
        }));

        $this->assertSame('Tony Stark', (string) $this->stringable('tony stark')->whenEndsWith(['kra', 'ark'], function ($stringable) {
            return $stringable->title();
        }, function ($stringable) {
            return $stringable->studly();
        }));

        $this->assertSame('tony stark', (string) $this->stringable('tony stark')->whenEndsWith(['xxx'], function ($stringable) {
            return $stringable->title();
        }));

        $this->assertSame('TonyStark', (string) $this->stringable('tony stark')->whenEndsWith(['tony', 'xxx'], function ($stringable) {
            return $stringable->title();
        }, function ($stringable) {
            return $stringable->studly();
        }));
    }

    public function testWhenDoesntEndWith()
    {
        $this->assertSame('Tony Stark', (string) $this->stringable('tony stark')->whenDoesntEndWith('ark', function ($stringable) {
            return $stringable->studly();
        }, function ($stringable) {
            return $stringable->title();
        }));

        $this->assertSame('Tony Stark', (string) $this->stringable('tony stark')->whenDoesntEndWith(['kra', 'ark'], function ($stringable) {
            return $stringable->studly();
        }, function ($stringable) {
            return $stringable->title();
        }));

        $this->assertSame('tony stark', (string) $this->stringable('tony stark')->whenDoesntEndWith(['xxx'], function ($stringable) {
            return $stringable;
        }));

        $this->assertSame('TonyStark', (string) $this->stringable('tony stark')->whenDoesntEndWith(['tony', 'xxx'], function ($stringable) {
            return $stringable->studly();
        }, function ($stringable) {
            return $stringable->title();
        }));
    }

    public function testWhenExactly()
    {
        $this->assertSame('Nailed it...!', (string) $this->stringable('Tony Stark')->whenExactly('Tony Stark', function ($stringable) {
            return 'Nailed it...!';
        }, function ($stringable) {
            return 'Swing and a miss...!';
        }));

        $this->assertSame('Swing and a miss...!', (string) $this->stringable('Tony Stark')->whenExactly('Iron Man', function ($stringable) {
            return 'Nailed it...!';
        }, function ($stringable) {
            return 'Swing and a miss...!';
        }));

        $this->assertSame('Tony Stark', (string) $this->stringable('Tony Stark')->whenExactly('Iron Man', function ($stringable) {
            return 'Nailed it...!';
        }));
    }

    public function testWhenNotExactly()
    {
        $this->assertSame(
            'Iron Man',
            (string) $this->stringable('Tony')->whenNotExactly('Tony Stark', function ($stringable) {
                return 'Iron Man';
            }));

        $this->assertSame(
            'Swing and a miss...!',
            (string) $this->stringable('Tony Stark')->whenNotExactly('Tony Stark', function ($stringable) {
                return 'Iron Man';
            }, function ($stringable) {
                return 'Swing and a miss...!';
            }));
    }

    public function testWhenIs()
    {
        $this->assertSame('Winner: /', (string) $this->stringable('/')->whenIs('/', function ($stringable) {
            return $stringable->prepend('Winner: ');
        }, function ($stringable) {
            return 'Try again';
        }));

        $this->assertSame('/', (string) $this->stringable('/')->whenIs(' /', function ($stringable) {
            return $stringable->prepend('Winner: ');
        }));

        $this->assertSame('Try again', (string) $this->stringable('/')->whenIs(' /', function ($stringable) {
            return $stringable->prepend('Winner: ');
        }, function ($stringable) {
            return 'Try again';
        }));

        $this->assertSame('Winner: foo/bar/baz', (string) $this->stringable('foo/bar/baz')->whenIs('foo/*', function ($stringable) {
            return $stringable->prepend('Winner: ');
        }));
    }

    public function testWhenIsAscii()
    {
        $this->assertSame('Ascii: A', (string) $this->stringable('A')->whenIsAscii(function ($stringable) {
            return $stringable->prepend('Ascii: ');
        }, function ($stringable) {
            return $stringable->prepend('Not Ascii: ');
        }));

        $this->assertSame('ù', (string) $this->stringable('ù')->whenIsAscii(function ($stringable) {
            return $stringable->prepend('Ascii: ');
        }));

        $this->assertSame('Not Ascii: ù', (string) $this->stringable('ù')->whenIsAscii(function ($stringable) {
            return $stringable->prepend('Ascii: ');
        }, function ($stringable) {
            return $stringable->prepend('Not Ascii: ');
        }));
    }

    public function testWhenIsUuid()
    {
        $this->assertSame('Uuid: 2cdc7039-65a6-4ac7-8e5d-d554a98e7b15', (string) $this->stringable('2cdc7039-65a6-4ac7-8e5d-d554a98e7b15')->whenIsUuid(function ($stringable) {
            return $stringable->prepend('Uuid: ');
        }, function ($stringable) {
            return $stringable->prepend('Not Uuid: ');
        }));

        $this->assertSame('2cdc7039-65a6-4ac7-8e5d-d554a98', (string) $this->stringable('2cdc7039-65a6-4ac7-8e5d-d554a98')->whenIsUuid(function ($stringable) {
            return $stringable->prepend('Uuid: ');
        }));

        $this->assertSame('Not Uuid: 2cdc7039-65a6-4ac7-8e5d-d554a98', (string) $this->stringable('2cdc7039-65a6-4ac7-8e5d-d554a98')->whenIsUuid(function ($stringable) {
            return $stringable->prepend('Uuid: ');
        }, function ($stringable) {
            return $stringable->prepend('Not Uuid: ');
        }));
    }

    public function testWhenIsUlid()
    {
        $this->assertSame('Ulid: 01GJSNW9MAF792C0XYY8RX6QFT', (string) $this->stringable('01GJSNW9MAF792C0XYY8RX6QFT')->whenIsUlid(function ($stringable) {
            return $stringable->prepend('Ulid: ');
        }, function ($stringable) {
            return $stringable->prepend('Not Ulid: ');
        }));

        $this->assertSame('2cdc7039-65a6-4ac7-8e5d-d554a98', (string) $this->stringable('2cdc7039-65a6-4ac7-8e5d-d554a98')->whenIsUlid(function ($stringable) {
            return $stringable->prepend('Ulid: ');
        }));

        $this->assertSame('Not Ulid: ss-01GJSNW9MAF792C0XYY8RX6QFT', (string) $this->stringable('ss-01GJSNW9MAF792C0XYY8RX6QFT')->whenIsUlid(function ($stringable) {
            return $stringable->prepend('Ulid: ');
        }, function ($stringable) {
            return $stringable->prepend('Not Ulid: ');
        }));
    }

    public function testWhenTest()
    {
        $this->assertSame('Winner: foo bar', (string) $this->stringable('foo bar')->whenTest('/bar/', function ($stringable) {
            return $stringable->prepend('Winner: ');
        }, function ($stringable) {
            return 'Try again';
        }));

        $this->assertSame('Try again', (string) $this->stringable('foo bar')->whenTest('/link/', function ($stringable) {
            return $stringable->prepend('Winner: ');
        }, function ($stringable) {
            return 'Try again';
        }));

        $this->assertSame('foo bar', (string) $this->stringable('foo bar')->whenTest('/link/', function ($stringable) {
            return $stringable->prepend('Winner: ');
        }));
    }

    public function testWhenStartsWith()
    {
        $this->assertSame('Tony Stark', (string) $this->stringable('tony stark')->whenStartsWith('ton', function ($stringable) {
            return $stringable->title();
        }, function ($stringable) {
            return $stringable->studly();
        }));

        $this->assertSame('Tony Stark', (string) $this->stringable('tony stark')->whenStartsWith(['ton', 'not'], function ($stringable) {
            return $stringable->title();
        }, function ($stringable) {
            return $stringable->studly();
        }));

        $this->assertSame('tony stark', (string) $this->stringable('tony stark')->whenStartsWith(['xxx'], function ($stringable) {
            return $stringable->title();
        }));

        $this->assertSame('Tony Stark', (string) $this->stringable('tony stark')->whenStartsWith(['tony', 'xxx'], function ($stringable) {
            return $stringable->title();
        }, function ($stringable) {
            return $stringable->studly();
        }));
    }

    public function testWhenDoesntStartWith()
    {
        $this->assertSame('Tony Stark', (string) $this->stringable('tony stark')->whenDoesntStartWith('ton', function ($stringable) {
            return $stringable->studly();
        }, function ($stringable) {
            return $stringable->title();
        }));

        $this->assertSame('Tony Stark', (string) $this->stringable('tony stark')->whenDoesntStartWith(['ton', 'not'], function ($stringable) {
            return $stringable->studly();
        }, function ($stringable) {
            return $stringable->title();
        }));

        $this->assertSame('tony stark', (string) $this->stringable('tony stark')->whenDoesntStartWith(['xxx'], function ($stringable) {
            return $stringable;
        }));

        $this->assertSame('Tony Stark', (string) $this->stringable('tony stark')->whenDoesntStartWith(['tony', 'xxx'], function ($stringable) {
            return $stringable->studly();
        }, function ($stringable) {
            return $stringable->title();
        }));
    }

    public function testWhenEmpty()
    {
        tap($this->stringable(), function ($stringable) {
            $this->assertSame($stringable, $stringable->whenEmpty(function () {
                //
            }));
        });

        $this->assertSame('empty', (string) $this->stringable()->whenEmpty(function () {
            return 'empty';
        }));

        $this->assertSame('not-empty', (string) $this->stringable('not-empty')->whenEmpty(function () {
            return 'empty';
        }));
    }

    public function testWhenNotEmpty()
    {
        tap($this->stringable(), function ($stringable) {
            $this->assertSame($stringable, $stringable->whenNotEmpty(function ($stringable) {
                return $stringable.'.';
            }));
        });

        $this->assertSame('', (string) $this->stringable()->whenNotEmpty(function ($stringable) {
            return $stringable.'.';
        }));

        $this->assertSame('Not empty.', (string) $this->stringable('Not empty')->whenNotEmpty(function ($stringable) {
            return $stringable.'.';
        }));
    }

    public function testWhenFalse()
    {
        $this->assertSame('when', (string) $this->stringable('when')->when(false, function ($stringable, $value) {
            return $stringable->append($value)->append('false');
        }));

        $this->assertSame('when false fallbacks to default', (string) $this->stringable('when false ')->when(false, function ($stringable, $value) {
            return $stringable->append($value);
        }, function ($stringable) {
            return $stringable->append('fallbacks to default');
        }));
    }

    public function testWhenTrue()
    {
        $this->assertSame('when true', (string) $this->stringable('when ')->when(true, function ($stringable) {
            return $stringable->append('true');
        }));

        $this->assertSame('gets a value from if', (string) $this->stringable('gets a value ')->when('from if', function ($stringable, $value) {
            return $stringable->append($value);
        }, function ($stringable) {
            return $stringable->append('fallbacks to default');
        }));
    }

    public function testUnlessTruthy()
    {
        $this->assertSame('unless', (string) $this->stringable('unless')->unless(1, function ($stringable, $value) {
            return $stringable->append($value)->append('true');
        }));

        $this->assertSame('unless true fallbacks to default with value 1',
            (string) $this->stringable('unless true ')->unless(1, function ($stringable, $value) {
                return $stringable->append($value);
            }, function ($stringable, $value) {
                return $stringable->append('fallbacks to default with value ')->append($value);
            }));
    }

    public function testUnlessFalsy()
    {
        $this->assertSame('unless 0', (string) $this->stringable('unless ')->unless(0, function ($stringable, $value) {
            return $stringable->append($value);
        }));

        $this->assertSame('gets the value 0',
            (string) $this->stringable('gets the value ')->unless(0, function ($stringable, $value) {
                return $stringable->append($value);
            }, function ($stringable) {
                return $stringable->append('fallbacks to default');
            }));
    }

    public function testTrimmedOnlyWhereNecessary()
    {
        $this->assertSame(' Taylor Otwell ', (string) $this->stringable(' Taylor Otwell ')->words(3));
        $this->assertSame(' Taylor...', (string) $this->stringable(' Taylor Otwell ')->words(1));
    }

    public function testTitle()
    {
        $this->assertSame('Jefferson Costella', (string) $this->stringable('jefferson costella')->title());
        $this->assertSame('Jefferson Costella', (string) $this->stringable('jefFErson coSTella')->title());
    }

    public function testWithoutWordsDoesntProduceError()
    {
        $nbsp = chr(0xC2).chr(0xA0);
        $this->assertSame(' ', (string) $this->stringable(' ')->words());
        $this->assertEquals($nbsp, (string) $this->stringable($nbsp)->words());
    }

    public function testAscii()
    {
        $this->assertSame('@', (string) $this->stringable('@')->ascii());
        $this->assertSame('u', (string) $this->stringable('ü')->ascii());
    }

    public function testTransliterate()
    {
        $this->assertSame('HHH', (string) $this->stringable('🎂🚧🏆')->transliterate('H'));
        $this->assertSame('Hello', (string) $this->stringable('🎂')->transliterate('Hello'));
    }

    public function testNewLine()
    {
        $this->assertSame('Laravel'.PHP_EOL, (string) $this->stringable('Laravel')->newLine());
        $this->assertSame('foo'.PHP_EOL.PHP_EOL.'bar', (string) $this->stringable('foo')->newLine(2)->append('bar'));
    }

    public function testAsciiWithSpecificLocale()
    {
        $this->assertSame('h H sht Sht a A ia yo', (string) $this->stringable('х Х щ Щ ъ Ъ иа йо')->ascii('bg'));
        $this->assertSame('ae oe ue Ae Oe Ue', (string) $this->stringable('ä ö ü Ä Ö Ü')->ascii('de'));
    }

    public function testStartsWith()
    {
        $this->assertTrue($this->stringable('jason')->startsWith('jas'));
        $this->assertTrue($this->stringable('jason')->startsWith('jason'));
        $this->assertTrue($this->stringable('jason')->startsWith(['jas']));
        $this->assertTrue($this->stringable('jason')->startsWith(['day', 'jas']));
        $this->assertTrue($this->stringable('jason')->startsWith(collect(['day', 'jas'])));
        $this->assertFalse($this->stringable('jason')->startsWith('day'));
        $this->assertFalse($this->stringable('jason')->startsWith(['day']));
        $this->assertFalse($this->stringable('jason')->startsWith(null));
        $this->assertFalse($this->stringable('jason')->startsWith([null]));
        $this->assertFalse($this->stringable('0123')->startsWith([null]));
        $this->assertTrue($this->stringable('0123')->startsWith(0));
        $this->assertFalse($this->stringable('jason')->startsWith('J'));
        $this->assertFalse($this->stringable('jason')->startsWith(''));
        $this->assertFalse($this->stringable('7')->startsWith(' 7'));
        $this->assertTrue($this->stringable('7a')->startsWith('7'));
        $this->assertTrue($this->stringable('7a')->startsWith(7));
        $this->assertTrue($this->stringable('7.12a')->startsWith(7.12));
        $this->assertFalse($this->stringable('7.12a')->startsWith(7.13));
        $this->assertTrue($this->stringable(7.123)->startsWith('7'));
        $this->assertTrue($this->stringable(7.123)->startsWith('7.12'));
        $this->assertFalse($this->stringable(7.123)->startsWith('7.13'));
        // Test for multibyte string support
        $this->assertTrue($this->stringable('Jönköping')->startsWith('Jö'));
        $this->assertTrue($this->stringable('Malmö')->startsWith('Malmö'));
        $this->assertFalse($this->stringable('Jönköping')->startsWith('Jonko'));
        $this->assertFalse($this->stringable('Malmö')->startsWith('Malmo'));
    }

    public function testDoesntStartWith()
    {
        $this->assertFalse($this->stringable('jason')->doesntStartWith('jas'));
        $this->assertFalse($this->stringable('jason')->doesntStartWith('jason'));
        $this->assertFalse($this->stringable('jason')->doesntStartWith(['jas']));
        $this->assertFalse($this->stringable('jason')->doesntStartWith(['day', 'jas']));
        $this->assertFalse($this->stringable('jason')->doesntStartWith(collect(['day', 'jas'])));
        $this->assertTrue($this->stringable('jason')->doesntStartWith('day'));
        $this->assertTrue($this->stringable('jason')->doesntStartWith(['day']));
        $this->assertTrue($this->stringable('jason')->doesntStartWith(null));
        $this->assertTrue($this->stringable('jason')->doesntStartWith([null]));
        $this->assertTrue($this->stringable('0123')->doesntStartWith([null]));
        $this->assertFalse($this->stringable('0123')->doesntStartWith(0));
        $this->assertTrue($this->stringable('jason')->doesntStartWith('J'));
        $this->assertTrue($this->stringable('jason')->doesntStartWith(''));
        $this->assertTrue($this->stringable('7')->doesntStartWith(' 7'));
        $this->assertFalse($this->stringable('7a')->doesntStartWith('7'));
        $this->assertFalse($this->stringable('7a')->doesntStartWith(7));
        $this->assertFalse($this->stringable('7.12a')->doesntStartWith(7.12));
        $this->assertTrue($this->stringable('7.12a')->doesntStartWith(7.13));
        $this->assertFalse($this->stringable(7.123)->doesntStartWith('7'));
        $this->assertFalse($this->stringable(7.123)->doesntStartWith('7.12'));
        $this->assertTrue($this->stringable(7.123)->doesntStartWith('7.13'));
        // Test for multibyte string support
        $this->assertFalse($this->stringable('Jönköping')->doesntStartWith('Jö'));
        $this->assertFalse($this->stringable('Malmö')->doesntStartWith('Malmö'));
        $this->assertTrue($this->stringable('Jönköping')->doesntStartWith('Jonko'));
        $this->assertTrue($this->stringable('Malmö')->doesntStartWith('Malmo'));
    }

    public function testEndsWith()
    {
        $this->assertTrue($this->stringable('jason')->endsWith('on'));
        $this->assertTrue($this->stringable('jason')->endsWith('jason'));
        $this->assertTrue($this->stringable('jason')->endsWith(['on']));
        $this->assertTrue($this->stringable('jason')->endsWith(['no', 'on']));
        $this->assertTrue($this->stringable('jason')->endsWith(collect(['no', 'on'])));
        $this->assertFalse($this->stringable('jason')->endsWith('no'));
        $this->assertFalse($this->stringable('jason')->endsWith(['no']));
        $this->assertFalse($this->stringable('jason')->endsWith(''));
        $this->assertFalse($this->stringable('jason')->endsWith([null]));
        $this->assertFalse($this->stringable('jason')->endsWith(null));
        $this->assertFalse($this->stringable('jason')->endsWith('N'));
        $this->assertFalse($this->stringable('7')->endsWith(' 7'));
        $this->assertTrue($this->stringable('a7')->endsWith('7'));
        $this->assertTrue($this->stringable('a7')->endsWith(7));
        $this->assertTrue($this->stringable('a7.12')->endsWith(7.12));
        $this->assertFalse($this->stringable('a7.12')->endsWith(7.13));
        $this->assertTrue($this->stringable(0.27)->endsWith('7'));
        $this->assertTrue($this->stringable(0.27)->endsWith('0.27'));
        $this->assertFalse($this->stringable(0.27)->endsWith('8'));
        // Test for multibyte string support
        $this->assertTrue($this->stringable('Jönköping')->endsWith('öping'));
        $this->assertTrue($this->stringable('Malmö')->endsWith('mö'));
        $this->assertFalse($this->stringable('Jönköping')->endsWith('oping'));
        $this->assertFalse($this->stringable('Malmö')->endsWith('mo'));
    }

    public function testDoesntEndWith()
    {
        $this->assertFalse($this->stringable('jason')->doesntEndWith('on'));
        $this->assertFalse($this->stringable('jason')->doesntEndWith('jason'));
        $this->assertFalse($this->stringable('jason')->doesntEndWith(['on']));
        $this->assertFalse($this->stringable('jason')->doesntEndWith(['no', 'on']));
        $this->assertFalse($this->stringable('jason')->doesntEndWith(collect(['no', 'on'])));
        $this->assertTrue($this->stringable('jason')->doesntEndWith('no'));
        $this->assertTrue($this->stringable('jason')->doesntEndWith(['no']));
        $this->assertTrue($this->stringable('jason')->doesntEndWith(''));
        $this->assertTrue($this->stringable('jason')->doesntEndWith([null]));
        $this->assertTrue($this->stringable('jason')->doesntEndWith(null));
        $this->assertTrue($this->stringable('jason')->doesntEndWith('N'));
        $this->assertTrue($this->stringable('7')->doesntEndWith(' 7'));
        $this->assertFalse($this->stringable('a7')->doesntEndWith('7'));
        $this->assertFalse($this->stringable('a7')->doesntEndWith(7));
        $this->assertFalse($this->stringable('a7.12')->doesntEndWith(7.12));
        $this->assertTrue($this->stringable('a7.12')->doesntEndWith(7.13));
        $this->assertFalse($this->stringable(0.27)->doesntEndWith('7'));
        $this->assertFalse($this->stringable(0.27)->doesntEndWith('0.27'));
        $this->assertTrue($this->stringable(0.27)->doesntEndWith('8'));
        // Test for multibyte string support
        $this->assertFalse($this->stringable('Jönköping')->doesntEndWith('öping'));
        $this->assertFalse($this->stringable('Malmö')->doesntEndWith('mö'));
        $this->assertTrue($this->stringable('Jönköping')->doesntEndWith('oping'));
        $this->assertTrue($this->stringable('Malmö')->doesntEndWith('mo'));
    }

    public function testExcerpt()
    {
        $this->assertSame('...is a beautiful morn...', (string) $this->stringable('This is a beautiful morning')->excerpt('beautiful', ['radius' => 5]));
    }

    public function testBefore()
    {
        $this->assertSame('han', (string) $this->stringable('hannah')->before('nah'));
        $this->assertSame('ha', (string) $this->stringable('hannah')->before('n'));
        $this->assertSame('ééé ', (string) $this->stringable('ééé hannah')->before('han'));
        $this->assertSame('hannah', (string) $this->stringable('hannah')->before('xxxx'));
        $this->assertSame('hannah', (string) $this->stringable('hannah')->before(''));
        $this->assertSame('han', (string) $this->stringable('han0nah')->before('0'));
        $this->assertSame('han', (string) $this->stringable('han0nah')->before(0));
        $this->assertSame('han', (string) $this->stringable('han2nah')->before(2));
    }

    public function testBeforeLast()
    {
        $this->assertSame('yve', (string) $this->stringable('yvette')->beforeLast('tte'));
        $this->assertSame('yvet', (string) $this->stringable('yvette')->beforeLast('t'));
        $this->assertSame('ééé ', (string) $this->stringable('ééé yvette')->beforeLast('yve'));
        $this->assertSame('', (string) $this->stringable('yvette')->beforeLast('yve'));
        $this->assertSame('yvette', (string) $this->stringable('yvette')->beforeLast('xxxx'));
        $this->assertSame('yvette', (string) $this->stringable('yvette')->beforeLast(''));
        $this->assertSame('yv0et', (string) $this->stringable('yv0et0te')->beforeLast('0'));
        $this->assertSame('yv0et', (string) $this->stringable('yv0et0te')->beforeLast(0));
        $this->assertSame('yv2et', (string) $this->stringable('yv2et2te')->beforeLast(2));
    }

    public function testBetween()
    {
        $this->assertSame('abc', (string) $this->stringable('abc')->between('', 'c'));
        $this->assertSame('abc', (string) $this->stringable('abc')->between('a', ''));
        $this->assertSame('abc', (string) $this->stringable('abc')->between('', ''));
        $this->assertSame('b', (string) $this->stringable('abc')->between('a', 'c'));
        $this->assertSame('b', (string) $this->stringable('dddabc')->between('a', 'c'));
        $this->assertSame('b', (string) $this->stringable('abcddd')->between('a', 'c'));
        $this->assertSame('b', (string) $this->stringable('dddabcddd')->between('a', 'c'));
        $this->assertSame('nn', (string) $this->stringable('hannah')->between('ha', 'ah'));
        $this->assertSame('a]ab[b', (string) $this->stringable('[a]ab[b]')->between('[', ']'));
        $this->assertSame('foo', (string) $this->stringable('foofoobar')->between('foo', 'bar'));
        $this->assertSame('bar', (string) $this->stringable('foobarbar')->between('foo', 'bar'));
    }

    public function testBetweenFirst()
    {
        $this->assertSame('abc', (string) $this->stringable('abc')->betweenFirst('', 'c'));
        $this->assertSame('abc', (string) $this->stringable('abc')->betweenFirst('a', ''));
        $this->assertSame('abc', (string) $this->stringable('abc')->betweenFirst('', ''));
        $this->assertSame('b', (string) $this->stringable('abc')->betweenFirst('a', 'c'));
        $this->assertSame('b', (string) $this->stringable('dddabc')->betweenFirst('a', 'c'));
        $this->assertSame('b', (string) $this->stringable('abcddd')->betweenFirst('a', 'c'));
        $this->assertSame('b', (string) $this->stringable('dddabcddd')->betweenFirst('a', 'c'));
        $this->assertSame('nn', (string) $this->stringable('hannah')->betweenFirst('ha', 'ah'));
        $this->assertSame('a', (string) $this->stringable('[a]ab[b]')->betweenFirst('[', ']'));
        $this->assertSame('foo', (string) $this->stringable('foofoobar')->betweenFirst('foo', 'bar'));
        $this->assertSame('', (string) $this->stringable('foobarbar')->betweenFirst('foo', 'bar'));
    }

    public function testAfter()
    {
        $this->assertSame('nah', (string) $this->stringable('hannah')->after('han'));
        $this->assertSame('nah', (string) $this->stringable('hannah')->after('n'));
        $this->assertSame('nah', (string) $this->stringable('ééé hannah')->after('han'));
        $this->assertSame('hannah', (string) $this->stringable('hannah')->after('xxxx'));
        $this->assertSame('hannah', (string) $this->stringable('hannah')->after(''));
        $this->assertSame('nah', (string) $this->stringable('han0nah')->after('0'));
        $this->assertSame('nah', (string) $this->stringable('han0nah')->after(0));
        $this->assertSame('nah', (string) $this->stringable('han2nah')->after(2));
    }

    public function testAfterLast()
    {
        $this->assertSame('tte', (string) $this->stringable('yvette')->afterLast('yve'));
        $this->assertSame('e', (string) $this->stringable('yvette')->afterLast('t'));
        $this->assertSame('e', (string) $this->stringable('ééé yvette')->afterLast('t'));
        $this->assertSame('', (string) $this->stringable('yvette')->afterLast('tte'));
        $this->assertSame('yvette', (string) $this->stringable('yvette')->afterLast('xxxx'));
        $this->assertSame('yvette', (string) $this->stringable('yvette')->afterLast(''));
        $this->assertSame('te', (string) $this->stringable('yv0et0te')->afterLast('0'));
        $this->assertSame('te', (string) $this->stringable('yv0et0te')->afterLast(0));
        $this->assertSame('te', (string) $this->stringable('yv2et2te')->afterLast(2));
        $this->assertSame('foo', (string) $this->stringable('----foo')->afterLast('---'));
    }

    public function testContains()
    {
        $this->assertTrue($this->stringable('taylor')->contains('ylo'));
        $this->assertTrue($this->stringable('taylor')->contains('taylor'));
        $this->assertTrue($this->stringable('taylor')->contains(['ylo']));
        $this->assertTrue($this->stringable('taylor')->contains(['xxx', 'ylo']));
        $this->assertTrue($this->stringable('taylor')->contains(collect(['xxx', 'ylo'])));
        $this->assertTrue($this->stringable('taylor')->contains(['LOR'], true));
        $this->assertFalse($this->stringable('taylor')->contains('xxx'));
        $this->assertFalse($this->stringable('taylor')->contains(['xxx']));
        $this->assertFalse($this->stringable('taylor')->contains(''));
    }

    public function testContainsAll()
    {
        $this->assertTrue($this->stringable('taylor otwell')->containsAll(['taylor', 'otwell']));
        $this->assertTrue($this->stringable('taylor otwell')->containsAll(['TAYLOR', 'OTWELL'], true));
        $this->assertTrue($this->stringable('taylor otwell')->containsAll(collect(['taylor', 'otwell'])));
        $this->assertTrue($this->stringable('taylor otwell')->containsAll(['taylor']));
        $this->assertFalse($this->stringable('taylor otwell')->containsAll(['taylor', 'xxx']));
    }

    public function testDoesntContain()
    {
        $this->assertTrue($this->stringable('taylor')->doesntContain('xxx'));
        $this->assertTrue($this->stringable('taylor')->doesntContain(['xxx']));
        $this->assertTrue($this->stringable('taylor')->doesntContain(['xxx', 'yyy']));
        $this->assertTrue($this->stringable('taylor')->doesntContain(collect(['xxx', 'yyy'])));
        $this->assertTrue($this->stringable('taylor')->doesntContain(''));
        $this->assertFalse($this->stringable('taylor')->doesntContain('ylo'));
        $this->assertFalse($this->stringable('taylor')->doesntContain('taylor'));
        $this->assertFalse($this->stringable('taylor')->doesntContain(['xxx', 'ylo']));
        $this->assertFalse($this->stringable('taylor')->doesntContain(['LOR'], true));
    }

    public function testParseCallback()
    {
        $this->assertEquals(['Class', 'method'], $this->stringable('Class@method')->parseCallback('foo'));
        $this->assertEquals(['Class', 'foo'], $this->stringable('Class')->parseCallback('foo'));
        $this->assertEquals(['Class', null], $this->stringable('Class')->parseCallback());
    }

    public function testSlug()
    {
        $this->assertSame('hello-world', (string) $this->stringable('hello world')->slug());
        $this->assertSame('hello-world', (string) $this->stringable('hello-world')->slug());
        $this->assertSame('hello-world', (string) $this->stringable('hello_world')->slug());
        $this->assertSame('hello_world', (string) $this->stringable('hello_world')->slug('_'));
        $this->assertSame('user-at-host', (string) $this->stringable('user@host')->slug());
        $this->assertSame('سلام-دنیا', (string) $this->stringable('سلام دنیا')->slug('-', null));
        $this->assertSame('sometext', (string) $this->stringable('some text')->slug(''));
        $this->assertSame('', (string) $this->stringable('')->slug(''));
        $this->assertSame('', (string) $this->stringable('')->slug());
    }

    public function testSquish()
    {
        $this->assertSame('words with spaces', (string) $this->stringable(' words  with   spaces ')->squish());
        $this->assertSame('words with spaces', (string) $this->stringable("words\t\twith\n\nspaces")->squish());
        $this->assertSame('words with spaces', (string) $this->stringable('
            words
            with
            spaces
        ')->squish());
        $this->assertSame('laravel php framework', (string) $this->stringable('   laravel   php   framework   ')->squish());
        $this->assertSame('123', (string) $this->stringable('   123    ')->squish());
        $this->assertSame('だ', (string) $this->stringable('だ')->squish());
        $this->assertSame('ム', (string) $this->stringable('ム')->squish());
        $this->assertSame('だ', (string) $this->stringable('   だ    ')->squish());
        $this->assertSame('ム', (string) $this->stringable('   ム    ')->squish());
        $this->assertSame('ム', (string) $this->stringable('﻿   ム ﻿﻿   ﻿')->squish());
    }

    public function testStart()
    {
        $this->assertSame('/test/string', (string) $this->stringable('test/string')->start('/'));
        $this->assertSame('/test/string', (string) $this->stringable('/test/string')->start('/'));
        $this->assertSame('/test/string', (string) $this->stringable('//test/string')->start('/'));
    }

    public function testFinish()
    {
        $this->assertSame('abbc', (string) $this->stringable('ab')->finish('bc'));
        $this->assertSame('abbc', (string) $this->stringable('abbcbc')->finish('bc'));
        $this->assertSame('abcbbc', (string) $this->stringable('abcbbcbc')->finish('bc'));
    }

    public function testIs()
    {
        $this->assertTrue($this->stringable('/')->is('/'));
        $this->assertFalse($this->stringable('/')->is(' /'));
        $this->assertFalse($this->stringable('/a')->is('/'));
        $this->assertTrue($this->stringable('foo/bar/baz')->is('foo/*'));

        $this->assertTrue($this->stringable('App\Class@method')->is('*@*'));
        $this->assertTrue($this->stringable('app\Class@')->is('*@*'));
        $this->assertTrue($this->stringable('@method')->is('*@*'));

        // is case sensitive
        $this->assertFalse($this->stringable('foo/bar/baz')->is('*BAZ*'));
        $this->assertFalse($this->stringable('foo/bar/baz')->is('*FOO*'));
        $this->assertFalse($this->stringable('a')->is('A'));

        // is not case sensitive
        $this->assertTrue($this->stringable('a')->is('A', true));
        $this->assertTrue($this->stringable('foo/bar/baz')->is('*BAZ*', true));
        $this->assertTrue($this->stringable('a/')->is(['A*', 'B*'], true));
        $this->assertFalse($this->stringable('f/')->is(['A*', 'B*'], true));
        $this->assertTrue($this->stringable('foo')->is('FOO', true));
        $this->assertTrue($this->stringable('foo/bar/baz')->is('*FOO*', true));
        $this->assertTrue($this->stringable('FOO/bar')->is('foo/*', true));

        // Accepts array of patterns
        $this->assertTrue($this->stringable('a/')->is(['a*', 'b*']));
        $this->assertTrue($this->stringable('b/')->is(['a*', 'b*']));
        $this->assertFalse($this->stringable('f/')->is(['a*', 'b*']));

        // numeric values and patterns
        $this->assertFalse($this->stringable(123)->is(['a*', 'b*']));
        $this->assertTrue($this->stringable(11211)->is(['*2*', 'b*']));

        $this->assertTrue($this->stringable('blah/baz/foo')->is('*/foo'));

        $valueObject = new StringableObjectStub('foo/bar/baz');
        $patternObject = new StringableObjectStub('foo/*');

        $this->assertTrue($this->stringable($valueObject)->is('foo/bar/baz'));
        $this->assertTrue($this->stringable($valueObject)->is($patternObject));

        // empty patterns
        $this->assertFalse($this->stringable('test')->is([]));
    }

    public function testIsWithMultilineStrings()
    {
        $this->assertFalse($this->stringable("/\n")->is('/'));
        $this->assertTrue($this->stringable("/\n")->is('/*'));
        $this->assertTrue($this->stringable("/\n")->is('*/*'));
        $this->assertTrue($this->stringable("\n/\n")->is('*/*'));

        $this->assertTrue($this->stringable("\n")->is('*'));
        $this->assertTrue($this->stringable("\n\n")->is('*'));
        $this->assertFalse($this->stringable("\n")->is(''));
        $this->assertFalse($this->stringable("\n\n")->is(''));

        $multilineValue = <<<'VALUE'
        <?php

        namespace Illuminate\Tests\Support;

        use Exception;
        VALUE;

        $this->assertTrue($this->stringable($multilineValue)->is($multilineValue));
        $this->assertTrue($this->stringable($multilineValue)->is('*'));
        $this->assertTrue($this->stringable($multilineValue)->is("*namespace Illuminate\Tests\*"));
        $this->assertFalse($this->stringable($multilineValue)->is("namespace Illuminate\Tests\*"));
        $this->assertFalse($this->stringable($multilineValue)->is("*namespace Illuminate\Tests"));
        $this->assertTrue($this->stringable($multilineValue)->is('<?php*'));
        $this->assertTrue($this->stringable($multilineValue)->is("<?php*namespace Illuminate\Tests\*"));
        $this->assertFalse($this->stringable($multilineValue)->is('use Exception;'));
        $this->assertFalse($this->stringable($multilineValue)->is('use Exception;*'));
        $this->assertTrue($this->stringable($multilineValue)->is('*use Exception;'));
    }

    public function testKebab()
    {
        $this->assertSame('laravel-php-framework', (string) $this->stringable('LaravelPhpFramework')->kebab());
    }

    public function testLower()
    {
        $this->assertSame('foo bar baz', (string) $this->stringable('FOO BAR BAZ')->lower());
        $this->assertSame('foo bar baz', (string) $this->stringable('fOo Bar bAz')->lower());
    }

    public function testUpper()
    {
        $this->assertSame('FOO BAR BAZ', (string) $this->stringable('foo bar baz')->upper());
        $this->assertSame('FOO BAR BAZ', (string) $this->stringable('foO bAr BaZ')->upper());
    }

    public function testLimit()
    {
        $this->assertSame('Laravel is...',
            (string) $this->stringable('Laravel is a free, open source PHP web application framework.')->limit(10)
        );
        $this->assertSame('这是一...', (string) $this->stringable('这是一段中文')->limit(6));

        $string = 'The PHP framework for web artisans.';
        $this->assertSame('The PHP...', (string) $this->stringable($string)->limit(7));
        $this->assertSame('The PHP', (string) $this->stringable($string)->limit(7, ''));
        $this->assertSame('The PHP framework for web artisans.', (string) $this->stringable($string)->limit(100));

        $nonAsciiString = '这是一段中文';
        $this->assertSame('这是一...', (string) $this->stringable($nonAsciiString)->limit(6));
        $this->assertSame('这是一', (string) $this->stringable($nonAsciiString)->limit(6, ''));
    }

    public function testLength()
    {
        $this->assertSame(11, $this->stringable('foo bar baz')->length());
        $this->assertSame(11, $this->stringable('foo bar baz')->length('UTF-8'));
    }

    public function testReplace()
    {
        $this->assertSame('foo/foo/foo', (string) $this->stringable('?/?/?')->replace('?', 'foo'));
        $this->assertSame('foo/foo/foo', (string) $this->stringable('x/x/x')->replace('X', 'foo', false));
        $this->assertSame('bar/bar', (string) $this->stringable('?/?')->replace('?', 'bar'));
        $this->assertSame('?/?/?', (string) $this->stringable('? ? ?')->replace(' ', '/'));
        $this->assertSame('foo/bar/baz/bam', (string) $this->stringable('?1/?2/?3/?4')->replace(['?1', '?2', '?3', '?4'], ['foo', 'bar', 'baz', 'bam']));
        $this->assertSame('?1/?2/?3/?4', (string) $this->stringable('foo/bar/baz/bam')->replace(['Foo', 'BaR', 'BAZ', 'bAm'], ['?1', '?2', '?3', '?4'], false));
        $this->assertSame('foo/bar/baz/bam', (string) $this->stringable('?1/?2/?3/?4')->replace(collect(['?1', '?2', '?3', '?4']), collect(['foo', 'bar', 'baz', 'bam'])));
    }

    public function testReplaceArray()
    {
        $this->assertSame('foo/bar/baz', (string) $this->stringable('?/?/?')->replaceArray('?', ['foo', 'bar', 'baz']));
        $this->assertSame('foo/bar/baz/?', (string) $this->stringable('?/?/?/?')->replaceArray('?', ['foo', 'bar', 'baz']));
        $this->assertSame('foo/bar', (string) $this->stringable('?/?')->replaceArray('?', ['foo', 'bar', 'baz']));
        $this->assertSame('?/?/?', (string) $this->stringable('?/?/?')->replaceArray('x', ['foo', 'bar', 'baz']));
        $this->assertSame('foo?/bar/baz', (string) $this->stringable('?/?/?')->replaceArray('?', ['foo?', 'bar', 'baz']));
        $this->assertSame('foo/bar', (string) $this->stringable('?/?')->replaceArray('?', [1 => 'foo', 2 => 'bar']));
        $this->assertSame('foo/bar', (string) $this->stringable('?/?')->replaceArray('?', ['x' => 'foo', 'y' => 'bar']));
        $this->assertSame('foo/bar', (string) $this->stringable('?/?')->replaceArray('?', collect(['x' => 'foo', 'y' => 'bar'])));
    }

    public function testReplaceFirst()
    {
        $this->assertSame('fooqux foobar', (string) $this->stringable('foobar foobar')->replaceFirst('bar', 'qux'));
        $this->assertSame('foo/qux? foo/bar?', (string) $this->stringable('foo/bar? foo/bar?')->replaceFirst('bar?', 'qux?'));
        $this->assertSame('foo foobar', (string) $this->stringable('foobar foobar')->replaceFirst('bar', ''));
        $this->assertSame('foobar foobar', (string) $this->stringable('foobar foobar')->replaceFirst('xxx', 'yyy'));
        $this->assertSame('foobar foobar', (string) $this->stringable('foobar foobar')->replaceFirst('', 'yyy'));
        // Test for multibyte string support
        $this->assertSame('Jxxxnköping Malmö', (string) $this->stringable('Jönköping Malmö')->replaceFirst('ö', 'xxx'));
        $this->assertSame('Jönköping Malmö', (string) $this->stringable('Jönköping Malmö')->replaceFirst('', 'yyy'));
    }

    public function testReplaceStart()
    {
        $this->assertSame('foobar foobar', (string) $this->stringable('foobar foobar')->replaceStart('bar', 'qux'));
        $this->assertSame('foo/bar? foo/bar?', (string) $this->stringable('foo/bar? foo/bar?')->replaceStart('bar?', 'qux?'));
        $this->assertSame('quxbar foobar', (string) $this->stringable('foobar foobar')->replaceStart('foo', 'qux'));
        $this->assertSame('qux? foo/bar?', (string) $this->stringable('foo/bar? foo/bar?')->replaceStart('foo/bar?', 'qux?'));
        $this->assertSame('bar foobar', (string) $this->stringable('foobar foobar')->replaceStart('foo', ''));
        $this->assertSame('1', (string) $this->stringable('0')->replaceStart(0, '1'));
        // Test for multibyte string support
        $this->assertSame('xxxnköping Malmö', (string) $this->stringable('Jönköping Malmö')->replaceStart('Jö', 'xxx'));
        $this->assertSame('Jönköping Malmö', (string) $this->stringable('Jönköping Malmö')->replaceStart('', 'yyy'));
    }

    public function testReplaceLast()
    {
        $this->assertSame('foobar fooqux', (string) $this->stringable('foobar foobar')->replaceLast('bar', 'qux'));
        $this->assertSame('foo/bar? foo/qux?', (string) $this->stringable('foo/bar? foo/bar?')->replaceLast('bar?', 'qux?'));
        $this->assertSame('foobar foo', (string) $this->stringable('foobar foobar')->replaceLast('bar', ''));
        $this->assertSame('foobar foobar', (string) $this->stringable('foobar foobar')->replaceLast('xxx', 'yyy'));
        $this->assertSame('foobar foobar', (string) $this->stringable('foobar foobar')->replaceLast('', 'yyy'));
        // Test for multibyte string support
        $this->assertSame('Malmö Jönkxxxping', (string) $this->stringable('Malmö Jönköping')->replaceLast('ö', 'xxx'));
        $this->assertSame('Malmö Jönköping', (string) $this->stringable('Malmö Jönköping')->replaceLast('', 'yyy'));
    }

    public function testReplaceEnd()
    {
        $this->assertSame('foobar fooqux', (string) $this->stringable('foobar foobar')->replaceEnd('bar', 'qux'));
        $this->assertSame('foo/bar? foo/qux?', (string) $this->stringable('foo/bar? foo/bar?')->replaceEnd('bar?', 'qux?'));
        $this->assertSame('foobar foo', (string) $this->stringable('foobar foobar')->replaceEnd('bar', ''));
        $this->assertSame('foobar foobar', (string) $this->stringable('foobar foobar')->replaceLast('xxx', 'yyy'));
        $this->assertSame('foobar foobar', (string) $this->stringable('foobar foobar')->replaceEnd('', 'yyy'));
        $this->assertSame('fooxxx foobar', (string) $this->stringable('fooxxx foobar')->replaceEnd('xxx', 'yyy'));

        // // Test for multibyte string support
        $this->assertSame('Malmö Jönköping', (string) $this->stringable('Malmö Jönköping')->replaceEnd('ö', 'xxx'));
        $this->assertSame('Malmö Jönkyyy', (string) $this->stringable('Malmö Jönköping')->replaceEnd('öping', 'yyy'));
    }

    public function testRemove()
    {
        $this->assertSame('Fbar', (string) $this->stringable('Foobar')->remove('o'));
        $this->assertSame('Foo', (string) $this->stringable('Foobar')->remove('bar'));
        $this->assertSame('oobar', (string) $this->stringable('Foobar')->remove('F'));
        $this->assertSame('Foobar', (string) $this->stringable('Foobar')->remove('f'));
        $this->assertSame('oobar', (string) $this->stringable('Foobar')->remove('f', false));

        $this->assertSame('Fbr', (string) $this->stringable('Foobar')->remove(['o', 'a']));
        $this->assertSame('Fbr', (string) $this->stringable('Foobar')->remove(collect(['o', 'a'])));
        $this->assertSame('Fooar', (string) $this->stringable('Foobar')->remove(['f', 'b']));
        $this->assertSame('ooar', (string) $this->stringable('Foobar')->remove(['f', 'b'], false));
        $this->assertSame('Foobar', (string) $this->stringable('Foo|bar')->remove(['f', '|']));
    }

    public function testReverse()
    {
        $this->assertSame('FooBar', (string) $this->stringable('raBooF')->reverse());
        $this->assertSame('Teniszütő', (string) $this->stringable('őtüzsineT')->reverse());
        $this->assertSame('❤MultiByte☆', (string) $this->stringable('☆etyBitluM❤')->reverse());
    }

    public function testSnake()
    {
        $this->assertSame('laravel_p_h_p_framework', (string) $this->stringable('LaravelPHPFramework')->snake());
        $this->assertSame('laravel_php_framework', (string) $this->stringable('LaravelPhpFramework')->snake());
        $this->assertSame('laravel php framework', (string) $this->stringable('LaravelPhpFramework')->snake(' '));
        $this->assertSame('laravel_php_framework', (string) $this->stringable('Laravel Php Framework')->snake());
        $this->assertSame('laravel_php_framework', (string) $this->stringable('Laravel    Php      Framework   ')->snake());
        // ensure cache keys don't overlap
        $this->assertSame('laravel__php__framework', (string) $this->stringable('LaravelPhpFramework')->snake('__'));
        $this->assertSame('laravel_php_framework_', (string) $this->stringable('LaravelPhpFramework_')->snake('_'));
        $this->assertSame('laravel_php_framework', (string) $this->stringable('laravel php Framework')->snake());
        $this->assertSame('laravel_php_frame_work', (string) $this->stringable('laravel php FrameWork')->snake());
        // prevent breaking changes
        $this->assertSame('foo-bar', (string) $this->stringable('foo-bar')->snake());
        $this->assertSame('foo-_bar', (string) $this->stringable('Foo-Bar')->snake());
        $this->assertSame('foo__bar', (string) $this->stringable('Foo_Bar')->snake());
        $this->assertSame('żółtałódka', (string) $this->stringable('ŻółtaŁódka')->snake());
    }

    public function testStudly()
    {
        $this->assertSame('LaravelPHPFramework', (string) $this->stringable('laravel_p_h_p_framework')->studly());
        $this->assertSame('LaravelPhpFramework', (string) $this->stringable('laravel_php_framework')->studly());
        $this->assertSame('LaravelPhPFramework', (string) $this->stringable('laravel-phP-framework')->studly());
        $this->assertSame('LaravelPhpFramework', (string) $this->stringable('laravel  -_-  php   -_-   framework   ')->studly());

        $this->assertSame('FooBar', (string) $this->stringable('fooBar')->studly());
        $this->assertSame('FooBar', (string) $this->stringable('foo_bar')->studly());
        $this->assertSame('FooBar', (string) $this->stringable('foo_bar')->studly()); // test cache
        $this->assertSame('FooBarBaz', (string) $this->stringable('foo-barBaz')->studly());
        $this->assertSame('FooBarBaz', (string) $this->stringable('foo-bar_baz')->studly());
    }

    public function testPascal()
    {
        $this->assertSame('LaravelPHPFramework', (string) $this->stringable('laravel_p_h_p_framework')->pascal());
        $this->assertSame('LaravelPhpFramework', (string) $this->stringable('laravel_php_framework')->pascal());
        $this->assertSame('LaravelPhPFramework', (string) $this->stringable('laravel-phP-framework')->pascal());
        $this->assertSame('LaravelPhpFramework', (string) $this->stringable('laravel  -_-  php   -_-   framework   ')->pascal());

        $this->assertSame('FooBar', (string) $this->stringable('fooBar')->pascal());
        $this->assertSame('FooBar', (string) $this->stringable('foo_bar')->pascal());
        $this->assertSame('FooBar', (string) $this->stringable('foo_bar')->pascal()); // test cache
        $this->assertSame('FooBarBaz', (string) $this->stringable('foo-barBaz')->pascal());
        $this->assertSame('FooBarBaz', (string) $this->stringable('foo-bar_baz')->pascal());
    }

    public function testCamel()
    {
        $this->assertSame('laravelPHPFramework', (string) $this->stringable('Laravel_p_h_p_framework')->camel());
        $this->assertSame('laravelPhpFramework', (string) $this->stringable('Laravel_php_framework')->camel());
        $this->assertSame('laravelPhPFramework', (string) $this->stringable('Laravel-phP-framework')->camel());
        $this->assertSame('laravelPhpFramework', (string) $this->stringable('Laravel  -_-  php   -_-   framework   ')->camel());

        $this->assertSame('fooBar', (string) $this->stringable('FooBar')->camel());
        $this->assertSame('fooBar', (string) $this->stringable('foo_bar')->camel());
        $this->assertSame('fooBar', (string) $this->stringable('foo_bar')->camel()); // test cache
        $this->assertSame('fooBarBaz', (string) $this->stringable('Foo-barBaz')->camel());
        $this->assertSame('fooBarBaz', (string) $this->stringable('foo-bar_baz')->camel());
    }

    public function testCharAt()
    {
        $this->assertEquals('р', $this->stringable('Привет, мир!')->charAt(1));
        $this->assertEquals('ち', $this->stringable('「こんにちは世界」')->charAt(4));
        $this->assertEquals('w', $this->stringable('Привет, world!')->charAt(8));
        $this->assertEquals('界', $this->stringable('「こんにちは世界」')->charAt(-2));
        $this->assertEquals(null, $this->stringable('「こんにちは世界」')->charAt(-200));
        $this->assertEquals(null, $this->stringable('Привет, мир!')->charAt('Привет, мир!', 100));
    }

    public function testSubstr()
    {
        $this->assertSame('Ё', (string) $this->stringable('БГДЖИЛЁ')->substr(-1));
        $this->assertSame('ЛЁ', (string) $this->stringable('БГДЖИЛЁ')->substr(-2));
        $this->assertSame('И', (string) $this->stringable('БГДЖИЛЁ')->substr(-3, 1));
        $this->assertSame('ДЖИЛ', (string) $this->stringable('БГДЖИЛЁ')->substr(2, -1));
        $this->assertSame('', (string) $this->stringable('БГДЖИЛЁ')->substr(4, -4));
        $this->assertSame('ИЛ', (string) $this->stringable('БГДЖИЛЁ')->substr(-3, -1));
        $this->assertSame('ГДЖИЛЁ', (string) $this->stringable('БГДЖИЛЁ')->substr(1));
        $this->assertSame('ГДЖ', (string) $this->stringable('БГДЖИЛЁ')->substr(1, 3));
        $this->assertSame('БГДЖ', (string) $this->stringable('БГДЖИЛЁ')->substr(0, 4));
        $this->assertSame('Ё', (string) $this->stringable('БГДЖИЛЁ')->substr(-1, 1));
        $this->assertSame('', (string) $this->stringable('Б')->substr(2));
    }

    public function testSwap()
    {
        $this->assertSame('PHP 8 is fantastic', (string) $this->stringable('PHP is awesome')->swap([
            'PHP' => 'PHP 8',
            'awesome' => 'fantastic',
        ]));
    }

    public function testSubstrCount()
    {
        $this->assertSame(3, $this->stringable('laravelPHPFramework')->substrCount('a'));
        $this->assertSame(0, $this->stringable('laravelPHPFramework')->substrCount('z'));
        $this->assertSame(1, $this->stringable('laravelPHPFramework')->substrCount('l', 2));
        $this->assertSame(0, $this->stringable('laravelPHPFramework')->substrCount('z', 2));
        $this->assertSame(1, $this->stringable('laravelPHPFramework')->substrCount('k', -1));
        $this->assertSame(1, $this->stringable('laravelPHPFramework')->substrCount('k', -1));
        $this->assertSame(1, $this->stringable('laravelPHPFramework')->substrCount('a', 1, 2));
        $this->assertSame(1, $this->stringable('laravelPHPFramework')->substrCount('a', 1, 2));
        $this->assertSame(3, $this->stringable('laravelPHPFramework')->substrCount('a', 1, -2));
        $this->assertSame(1, $this->stringable('laravelPHPFramework')->substrCount('a', -10, -3));
    }

    public function testPosition()
    {
        $this->assertSame(7, $this->stringable('Hello, World!')->position('W'));
        $this->assertSame(10, $this->stringable('This is a test string.')->position('test'));
        $this->assertSame(23, $this->stringable('This is a test string, test again.')->position('test', 15));
        $this->assertSame(0, $this->stringable('Hello, World!')->position('Hello'));
        $this->assertSame(7, $this->stringable('Hello, World!')->position('World!'));
        $this->assertSame(10, $this->stringable('This is a tEsT string.')->position('tEsT', 0, 'UTF-8'));
        $this->assertSame(7, $this->stringable('Hello, World!')->position('W', -6));
        $this->assertSame(18, $this->stringable('Äpfel, Birnen und Kirschen')->position('Kirschen', -10, 'UTF-8'));
        $this->assertSame(9, $this->stringable('@%€/=!"][$')->position('$', 0, 'UTF-8'));
        $this->assertFalse($this->stringable('Hello, World!')->position('w', 0, 'UTF-8'));
        $this->assertFalse($this->stringable('Hello, World!')->position('X', 0, 'UTF-8'));
        $this->assertFalse($this->stringable('')->position('test'));
        $this->assertFalse($this->stringable('Hello, World!')->position('X'));
    }

    public function testSubstrReplace()
    {
        $this->assertSame('12:00', (string) $this->stringable('1200')->substrReplace(':', 2, 0));
        $this->assertSame('The Laravel Framework', (string) $this->stringable('The Framework')->substrReplace('Laravel ', 4, 0));
        $this->assertSame('Laravel – The PHP Framework for Web Artisans', (string) $this->stringable('Laravel Framework')->substrReplace('– The PHP Framework for Web Artisans', 8));
    }

    public function testPadBoth()
    {
        $this->assertSame('__Alien___', (string) $this->stringable('Alien')->padBoth(10, '_'));
        $this->assertSame('  Alien   ', (string) $this->stringable('Alien')->padBoth(10));
        $this->assertSame('  ❤MultiByte☆   ', (string) $this->stringable('❤MultiByte☆')->padBoth(16));
    }

    public function testPadLeft()
    {
        $this->assertSame('-=-=-Alien', (string) $this->stringable('Alien')->padLeft(10, '-='));
        $this->assertSame('     Alien', (string) $this->stringable('Alien')->padLeft(10));
        $this->assertSame('     ❤MultiByte☆', (string) $this->stringable('❤MultiByte☆')->padLeft(16));
    }

    public function testPadRight()
    {
        $this->assertSame('Alien-----', (string) $this->stringable('Alien')->padRight(10, '-'));
        $this->assertSame('Alien     ', (string) $this->stringable('Alien')->padRight(10));
        $this->assertSame('❤MultiByte☆     ', (string) $this->stringable('❤MultiByte☆')->padRight(16));
    }

    public function testExplode()
    {
        $this->assertInstanceOf(Collection::class, $this->stringable('Foo Bar Baz')->explode(' '));

        $this->assertSame('["Foo","Bar","Baz"]', (string) $this->stringable('Foo Bar Baz')->explode(' '));

        //  with limit
        $this->assertSame('["Foo","Bar Baz"]', (string) $this->stringable('Foo Bar Baz')->explode(' ', 2));
        $this->assertSame('["Foo","Bar"]', (string) $this->stringable('Foo Bar Baz')->explode(' ', -1));
    }

    public function testChunk()
    {
        $chunks = $this->stringable('foobarbaz')->split(3);

        $this->assertInstanceOf(Collection::class, $chunks);
        $this->assertSame(['foo', 'bar', 'baz'], $chunks->all());
    }

    public function testJsonSerialize()
    {
        $this->assertSame('"foo"', json_encode($this->stringable('foo')));
        $this->assertSame('"laravel-php-framework"', json_encode($this->stringable('LaravelPhpFramework')->kebab()));
        $this->assertSame('["laravel-php-framework"]', json_encode([$this->stringable('LaravelPhpFramework')->kebab()]));
        $this->assertSame('{"title":"laravel-php-framework"}', json_encode(['title' => $this->stringable('LaravelPhpFramework')->kebab()]));
    }

    public function testTap()
    {
        $stringable = $this->stringable('foobarbaz');

        $fromTheTap = '';

        $stringable = $stringable->tap(function (Stringable $string) use (&$fromTheTap) {
            $fromTheTap = $string->substr(0, 3);
        });

        $this->assertSame('foo', (string) $fromTheTap);
        $this->assertSame('foobarbaz', (string) $stringable);
    }

    public function testPipe()
    {
        $callback = function ($stringable) {
            return 'bar';
        };

        $this->assertInstanceOf(Stringable::class, $this->stringable('foo')->pipe($callback));
        $this->assertSame('bar', (string) $this->stringable('foo')->pipe($callback));
    }

    public function testMarkdown()
    {
        $this->assertEquals("<p><em>hello world</em></p>\n", $this->stringable('*hello world*')->markdown());
        $this->assertEquals("<h1>hello world</h1>\n", $this->stringable('# hello world')->markdown());

        $extension = new class implements ExtensionInterface
        {
            public bool $configured = false;

            public function register(EnvironmentBuilderInterface $environment): void
            {
                $this->configured = true;
            }
        };
        $this->stringable('# hello world')->markdown([], [$extension]);
        $this->assertTrue($extension->configured);
    }

    public function testInlineMarkdown()
    {
        $this->assertEquals("<em>hello world</em>\n", $this->stringable('*hello world*')->inlineMarkdown());
        $this->assertEquals("<a href=\"https://laravel.com\"><strong>Laravel</strong></a>\n", $this->stringable('[**Laravel**](https://laravel.com)')->inlineMarkdown());

        $extension = new class implements ExtensionInterface
        {
            public bool $configured = false;

            public function register(EnvironmentBuilderInterface $environment): void
            {
                $this->configured = true;
            }
        };

        $this->stringable('# hello world')->inlineMarkdown([], [$extension]);
        $this->assertTrue($extension->configured);
    }

    public function testMask()
    {
        $this->assertSame('tay*************', (string) $this->stringable('taylor@email.com')->mask('*', 3));
        $this->assertSame('******@email.com', (string) $this->stringable('taylor@email.com')->mask('*', 0, 6));
        $this->assertSame('tay*************', (string) $this->stringable('taylor@email.com')->mask('*', -13));
        $this->assertSame('tay***@email.com', (string) $this->stringable('taylor@email.com')->mask('*', -13, 3));

        $this->assertSame('****************', (string) $this->stringable('taylor@email.com')->mask('*', -17));
        $this->assertSame('*****r@email.com', (string) $this->stringable('taylor@email.com')->mask('*', -99, 5));

        $this->assertSame('taylor@email.com', (string) $this->stringable('taylor@email.com')->mask('*', 16));
        $this->assertSame('taylor@email.com', (string) $this->stringable('taylor@email.com')->mask('*', 16, 99));

        $this->assertSame('taylor@email.com', (string) $this->stringable('taylor@email.com')->mask('', 3));

        $this->assertSame('taysssssssssssss', (string) $this->stringable('taylor@email.com')->mask('something', 3));

        $this->assertSame('这是一***', (string) $this->stringable('这是一段中文')->mask('*', 3));
        $this->assertSame('**一段中文', (string) $this->stringable('这是一段中文')->mask('*', 0, 2));
    }

    public function testRepeat()
    {
        $this->assertSame('aaaaa', (string) $this->stringable('a')->repeat(5));
        $this->assertSame('', (string) $this->stringable('')->repeat(5));
    }

    public function testWordCount()
    {
        $this->assertEquals(2, $this->stringable('Hello, world!')->wordCount());
        $this->assertEquals(10, $this->stringable('Hi, this is my first contribution to the Laravel framework.')->wordCount());
    }

    public function testWrap()
    {
        $this->assertEquals('This is me!', $this->stringable('is')->wrap('This ', ' me!'));
        $this->assertEquals('"value"', $this->stringable('value')->wrap('"'));
    }

    public function testUnwrap()
    {
        $this->assertEquals('value', $this->stringable('"value"')->unwrap('"'));
        $this->assertEquals('bar', $this->stringable('foo-bar-baz')->unwrap('foo-', '-baz'));
        $this->assertEquals('some: "json"', $this->stringable('{some: "json"}')->unwrap('{', '}'));
    }

    public function testToHtmlString()
    {
        $this->assertEquals(
            new HtmlString('<h1>Test String</h1>'),
            $this->stringable('<h1>Test String</h1>')->toHtmlString()
        );
    }

    public function testStripTags()
    {
        $this->assertSame('beforeafter', (string) $this->stringable('before<br>after')->stripTags());
        $this->assertSame('before<br>after', (string) $this->stringable('before<br>after')->stripTags('<br>'));
        $this->assertSame('before<br>after', (string) $this->stringable('<strong>before</strong><br>after')->stripTags('<br>'));
        $this->assertSame('<strong>before</strong><br>after', (string) $this->stringable('<strong>before</strong><br>after')->stripTags('<br><strong>'));
    }

    public function testReplaceMatches()
    {
        $stringable = $this->stringable('Hello world!');
        $result = $stringable->replaceMatches('/world/', function ($match) {
            return strtoupper($match[0]);
        });

        $this->assertSame('Hello WORLD!', $result->value);

        $stringable = $this->stringable('apple orange apple');
        $result = $stringable->replaceMatches('/apple/', 'fruit', 1);

        $this->assertSame('fruit orange apple', $result->value);
    }

    public function testScan()
    {
        $this->assertSame([123456], $this->stringable('SN/123456')->scan('SN/%d')->toArray());
        $this->assertSame(['Otwell', 'Taylor'], $this->stringable('Otwell, Taylor')->scan('%[^,],%s')->toArray());
        $this->assertSame(['filename', 'jpg'], $this->stringable('filename.jpg')->scan('%[^.].%s')->toArray());
    }

    public function testGet()
    {
        $this->assertSame('foo', $this->stringable('foo')->value());
        $this->assertSame('foo', $this->stringable('foo')->toString());
    }

    public function testExactly()
    {
        $this->assertTrue($this->stringable('foo')->exactly($this->stringable('foo')));
        $this->assertTrue($this->stringable('foo')->exactly('foo'));

        $this->assertFalse($this->stringable('Foo')->exactly($this->stringable('foo')));
        $this->assertFalse($this->stringable('Foo')->exactly('foo'));
        $this->assertFalse($this->stringable('[]')->exactly([]));
        $this->assertFalse($this->stringable('0')->exactly(0));
    }

    public function testToInteger()
    {
        $this->assertSame(123, $this->stringable('123')->toInteger());
        $this->assertSame(456, $this->stringable(456)->toInteger());
        $this->assertSame(78, $this->stringable('078')->toInteger());
        $this->assertSame(901, $this->stringable(' 901')->toInteger());
        $this->assertSame(0, $this->stringable('nan')->toInteger());
        $this->assertSame(1, $this->stringable('1ab')->toInteger());
        $this->assertSame(2, $this->stringable('2_000')->toInteger());
    }

    public function testToFloat()
    {
        $this->assertSame(1.23, $this->stringable('1.23')->toFloat());
        $this->assertSame(45.6, $this->stringable(45.6)->toFloat());
        $this->assertSame(.6, $this->stringable('.6')->toFloat());
        $this->assertSame(0.78, $this->stringable('0.78')->toFloat());
        $this->assertSame(90.1, $this->stringable(' 90.1')->toFloat());
        $this->assertSame(0.0, $this->stringable('nan')->toFloat());
        $this->assertSame(1.0, $this->stringable('1.ab')->toFloat());
        $this->assertSame(1e3, $this->stringable('1e3')->toFloat());
    }

    public function testBooleanMethod()
    {
        $this->assertTrue($this->stringable(true)->toBoolean());
        $this->assertTrue($this->stringable('true')->toBoolean());
        $this->assertFalse($this->stringable('false')->toBoolean());
        $this->assertTrue($this->stringable('1')->toBoolean());
        $this->assertFalse($this->stringable('0')->toBoolean());
        $this->assertTrue($this->stringable('on')->toBoolean());
        $this->assertFalse($this->stringable('off')->toBoolean());
        $this->assertTrue($this->stringable('yes')->toBoolean());
        $this->assertFalse($this->stringable('no')->toBoolean());
    }

    public function testNumbers()
    {
        $this->assertSame('5551234567', (string) $this->stringable('(555) 123-4567')->numbers());
    }

    public function testToDate()
    {
        $current = Carbon::create(2020, 1, 1, 16, 30, 25);

        $this->assertEquals($current, $this->stringable('20-01-01 16:30:25')->toDate());
        $this->assertEquals($current, $this->stringable('1577896225')->toDate('U'));
        $this->assertEquals($current, $this->stringable('20-01-01 13:30:25')->toDate(null, 'America/Santiago'));

        $this->assertTrue($this->stringable('2020-01-01')->toDate()->isSameDay($current));
        $this->assertTrue($this->stringable('16:30:25')->toDate()->isSameSecond('16:30:25'));
    }

    public function testToDateThrowsException()
    {
        $this->expectException(\Carbon\Exceptions\InvalidFormatException::class);

        $this->stringable('not a date')->toDate();
    }

    public function testToUri()
    {
        $sentence = 'Laravel is a PHP framework. You can access the docs in: {https://laravel.com/docs}';

        $uri = $this->stringable($sentence)->between('{', '}')->toUri();

        $this->assertInstanceOf(Uri::class, $uri);
        $this->assertSame('https://laravel.com/docs', (string) $uri);
        $this->assertSame('https://laravel.com/docs', $uri->toHtml());
    }

    public function testArrayAccess()
    {
        $str = $this->stringable('my string');
        $this->assertSame('m', $str[0]);
        $this->assertSame('t', $str[4]);
        $this->assertTrue(isset($str[2]));
        $this->assertFalse(isset($str[10]));
    }

    public function testToBase64()
    {
        $this->assertSame(base64_encode('foo'), (string) $this->stringable('foo')->toBase64());
        $this->assertSame(base64_encode('foobar'), (string) $this->stringable('foobar')->toBase64());
        $this->assertSame(base64_encode('foobarbaz'), (string) $this->stringable('foobarbaz')->toBase64());
    }

    public function testFromBase64()
    {
        $this->assertSame('foo', (string) $this->stringable(base64_encode('foo'))->fromBase64());
        $this->assertSame('foobar', (string) $this->stringable(base64_encode('foobar'))->fromBase64(true));
        $this->assertSame('foobarbaz', (string) $this->stringable(base64_encode('foobarbaz'))->fromBase64());
    }

    public function testHash()
    {
        $this->assertSame(hash('xxh3', 'foo'), (string) $this->stringable('foo')->hash('xxh3'));
        $this->assertSame(hash('xxh3', 'foobar'), (string) $this->stringable('foobar')->hash('xxh3'));
        $this->assertSame(hash('sha256', 'foobarbaz'), (string) $this->stringable('foobarbaz')->hash('sha256'));
    }

    public function testEncryptAndDecrypt()
    {
        Container::setInstance($this->container = new Container);

        $this->container->bind('encrypter', fn () => new Encrypter(str_repeat('b', 16)));

        $encrypted = $this->stringable('foo')->encrypt();

        $this->assertNotSame('foo', $encrypted->value());
        $this->assertSame('foo', $encrypted->decrypt()->value());
    }
}
