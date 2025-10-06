<?php

namespace Illuminate\Tests\Support;

use Exception;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\UuidInterface;
use ReflectionClass;
use ValueError;

class SupportStrTest extends TestCase
{
    /** {@inheritdoc} */
    #[\Override]
    protected function tearDown(): void
    {
        Str::createRandomStringsNormally();
    }

    public function testStringCanBeLimitedByWords(): void
    {
        $this->assertSame('Taylor...', Str::words('Taylor Otwell', 1));
        $this->assertSame('Taylor___', Str::words('Taylor Otwell', 1, '___'));
        $this->assertSame('Taylor Otwell', Str::words('Taylor Otwell', 3));
        $this->assertSame('Taylor Otwell', Str::words('Taylor Otwell', -1, '...'));
        $this->assertSame('', Str::words('', 3, '...'));
    }

    public function testStringCanBeLimitedByWordsNonAscii()
    {
        $this->assertSame('这是...', Str::words('这是 段中文', 1));
        $this->assertSame('这是___', Str::words('这是 段中文', 1, '___'));
        $this->assertSame('这是-段中文', Str::words('这是-段中文', 3, '___'));
        $this->assertSame('这是___', Str::words('这是     段中文', 1, '___'));
    }

    public function testStringTrimmedOnlyWhereNecessary()
    {
        $this->assertSame(' Taylor Otwell ', Str::words(' Taylor Otwell ', 3));
        $this->assertSame(' Taylor...', Str::words(' Taylor Otwell ', 1));
    }

    public function testStringTitle()
    {
        $this->assertSame('Jefferson Costella', Str::title('jefferson costella'));
        $this->assertSame('Jefferson Costella', Str::title('jefFErson coSTella'));

        $this->assertSame('', Str::title(''));
        $this->assertSame('123 Laravel', Str::title('123 laravel'));
        $this->assertSame('❤Laravel', Str::title('❤laravel'));
        $this->assertSame('Laravel ❤', Str::title('laravel ❤'));
        $this->assertSame('Laravel123', Str::title('laravel123'));
        $this->assertSame('Laravel123', Str::title('Laravel123'));

        $longString = 'lorem ipsum '.str_repeat('dolor sit amet ', 1000);
        $expectedResult = 'Lorem Ipsum Dolor Sit Amet '.str_repeat('Dolor Sit Amet ', 999);
        $this->assertSame($expectedResult, Str::title($longString));
    }

    public function testStringHeadline()
    {
        $this->assertSame('Jefferson Costella', Str::headline('jefferson costella'));
        $this->assertSame('Jefferson Costella', Str::headline('jefFErson coSTella'));
        $this->assertSame('Jefferson Costella Uses Laravel', Str::headline('jefferson_costella uses-_Laravel'));
        $this->assertSame('Jefferson Costella Uses Laravel', Str::headline('jefferson_costella uses__Laravel'));

        $this->assertSame('Laravel P H P Framework', Str::headline('laravel_p_h_p_framework'));
        $this->assertSame('Laravel P H P Framework', Str::headline('laravel _p _h _p _framework'));
        $this->assertSame('Laravel Php Framework', Str::headline('laravel_php_framework'));
        $this->assertSame('Laravel Ph P Framework', Str::headline('laravel-phP-framework'));
        $this->assertSame('Laravel Php Framework', Str::headline('laravel  -_-  php   -_-   framework   '));

        $this->assertSame('Foo Bar', Str::headline('fooBar'));
        $this->assertSame('Foo Bar', Str::headline('foo_bar'));
        $this->assertSame('Foo Bar Baz', Str::headline('foo-barBaz'));
        $this->assertSame('Foo Bar Baz', Str::headline('foo-bar_baz'));

        $this->assertSame('Öffentliche Überraschungen', Str::headline('öffentliche-überraschungen'));
        $this->assertSame('Öffentliche Überraschungen', Str::headline('-_öffentliche_überraschungen_-'));
        $this->assertSame('Öffentliche Überraschungen', Str::headline('-öffentliche überraschungen'));

        $this->assertSame('Sind Öde Und So', Str::headline('sindÖdeUndSo'));

        $this->assertSame('Orwell 1984', Str::headline('orwell 1984'));
        $this->assertSame('Orwell 1984', Str::headline('orwell   1984'));
        $this->assertSame('Orwell 1984', Str::headline('-orwell-1984 -'));
        $this->assertSame('Orwell 1984', Str::headline(' orwell_- 1984 '));
    }

    public function testStringApa()
    {
        $this->assertSame('Tom and Jerry', Str::apa('tom and jerry'));
        $this->assertSame('Tom and Jerry', Str::apa('TOM AND JERRY'));
        $this->assertSame('Tom and Jerry', Str::apa('Tom And Jerry'));

        $this->assertSame('Back to the Future', Str::apa('back to the future'));
        $this->assertSame('Back to the Future', Str::apa('BACK TO THE FUTURE'));
        $this->assertSame('Back to the Future', Str::apa('Back To The Future'));

        $this->assertSame('This, Then That', Str::apa('this, then that'));
        $this->assertSame('This, Then That', Str::apa('THIS, THEN THAT'));
        $this->assertSame('This, Then That', Str::apa('This, Then That'));

        $this->assertSame('Bond. James Bond.', Str::apa('bond. james bond.'));
        $this->assertSame('Bond. James Bond.', Str::apa('BOND. JAMES BOND.'));
        $this->assertSame('Bond. James Bond.', Str::apa('Bond. James Bond.'));

        $this->assertSame('Self-Report', Str::apa('self-report'));
        $this->assertSame('Self-Report', Str::apa('Self-report'));
        $this->assertSame('Self-Report', Str::apa('SELF-REPORT'));

        $this->assertSame('As the World Turns, So Are the Days of Our Lives', Str::apa('as the world turns, so are the days of our lives'));
        $this->assertSame('As the World Turns, So Are the Days of Our Lives', Str::apa('AS THE WORLD TURNS, SO ARE THE DAYS OF OUR LIVES'));
        $this->assertSame('As the World Turns, So Are the Days of Our Lives', Str::apa('As The World Turns, So Are The Days Of Our Lives'));

        $this->assertSame('To Kill a Mockingbird', Str::apa('to kill a mockingbird'));
        $this->assertSame('To Kill a Mockingbird', Str::apa('TO KILL A MOCKINGBIRD'));
        $this->assertSame('To Kill a Mockingbird', Str::apa('To Kill A Mockingbird'));

        $this->assertSame('Être Écrivain Commence par Être un Lecteur.', Str::apa('Être écrivain commence par être un lecteur.'));
        $this->assertSame('Être Écrivain Commence par Être un Lecteur.', Str::apa('Être Écrivain Commence par Être un Lecteur.'));
        $this->assertSame('Être Écrivain Commence par Être un Lecteur.', Str::apa('ÊTRE ÉCRIVAIN COMMENCE PAR ÊTRE UN LECTEUR.'));

        $this->assertSame("C'est-à-Dire.", Str::apa("c'est-à-dire."));
        $this->assertSame("C'est-à-Dire.", Str::apa("C'est-à-Dire."));
        $this->assertSame("C'est-à-Dire.", Str::apa("C'EsT-À-DIRE."));

        $this->assertSame('Устное Слово – Не Воробей. Как Только Он Вылетит, Его Не Поймаешь.', Str::apa('устное слово – не воробей. как только он вылетит, его не поймаешь.'));
        $this->assertSame('Устное Слово – Не Воробей. Как Только Он Вылетит, Его Не Поймаешь.', Str::apa('Устное Слово – Не Воробей. Как Только Он Вылетит, Его Не Поймаешь.'));
        $this->assertSame('Устное Слово – Не Воробей. Как Только Он Вылетит, Его Не Поймаешь.', Str::apa('УСТНОЕ СЛОВО – НЕ ВОРОБЕЙ. КАК ТОЛЬКО ОН ВЫЛЕТИТ, ЕГО НЕ ПОЙМАЕШЬ.'));

        $this->assertSame('', Str::apa(''));
        $this->assertSame('   ', Str::apa('   '));
    }

    public function testStringWithoutWordsDoesntProduceError(): void
    {
        $nbsp = chr(0xC2).chr(0xA0);
        $this->assertSame(' ', Str::words(' '));
        $this->assertEquals($nbsp, Str::words($nbsp));
        $this->assertSame('   ', Str::words('   '));
        $this->assertSame("\t\t\t", Str::words("\t\t\t"));
    }

    public function testStringAscii(): void
    {
        $this->assertSame('@', Str::ascii('@'));
        $this->assertSame('u', Str::ascii('ü'));
        $this->assertSame('', Str::ascii(''));
        $this->assertSame('a!2e', Str::ascii('a!2ë'));
    }

    public function testStringAsciiWithSpecificLocale()
    {
        $this->assertSame('h H sht Sht a A ia yo', Str::ascii('х Х щ Щ ъ Ъ иа йо', 'bg'));
        $this->assertSame('ae oe ue Ae Oe Ue', Str::ascii('ä ö ü Ä Ö Ü', 'de'));
    }

    public function testStartsWith()
    {
        $this->assertTrue(Str::startsWith('jason', 'jas'));
        $this->assertTrue(Str::startsWith('jason', 'jason'));
        $this->assertTrue(Str::startsWith('jason', ['jas']));
        $this->assertTrue(Str::startsWith('jason', ['day', 'jas']));
        $this->assertTrue(Str::startsWith('jason', collect(['day', 'jas'])));
        $this->assertFalse(Str::startsWith('jason', 'day'));
        $this->assertFalse(Str::startsWith('jason', ['day']));
        $this->assertFalse(Str::startsWith('jason', null));
        $this->assertFalse(Str::startsWith('jason', [null]));
        $this->assertFalse(Str::startsWith('0123', [null]));
        $this->assertTrue(Str::startsWith('0123', 0));
        $this->assertFalse(Str::startsWith('jason', 'J'));
        $this->assertFalse(Str::startsWith('jason', ''));
        $this->assertFalse(Str::startsWith('', ''));
        $this->assertFalse(Str::startsWith('7', ' 7'));
        $this->assertTrue(Str::startsWith('7a', '7'));
        $this->assertTrue(Str::startsWith('7a', 7));
        $this->assertTrue(Str::startsWith('7.12a', 7.12));
        $this->assertFalse(Str::startsWith('7.12a', 7.13));
        $this->assertTrue(Str::startsWith(7.123, '7'));
        $this->assertTrue(Str::startsWith(7.123, '7.12'));
        $this->assertFalse(Str::startsWith(7.123, '7.13'));
        $this->assertFalse(Str::startsWith(null, 'Marc'));
        // Test for multibyte string support
        $this->assertTrue(Str::startsWith('Jönköping', 'Jö'));
        $this->assertTrue(Str::startsWith('Malmö', 'Malmö'));
        $this->assertFalse(Str::startsWith('Jönköping', 'Jonko'));
        $this->assertFalse(Str::startsWith('Malmö', 'Malmo'));
        $this->assertTrue(Str::startsWith('你好', '你'));
        $this->assertFalse(Str::startsWith('你好', '好'));
        $this->assertFalse(Str::startsWith('你好', 'a'));
    }

    public function testDoesntStartWith()
    {
        $this->assertFalse(Str::doesntStartWith('jason', 'jas'));
        $this->assertFalse(Str::doesntStartWith('jason', 'jason'));
        $this->assertFalse(Str::doesntStartWith('jason', ['jas']));
        $this->assertFalse(Str::doesntStartWith('jason', ['day', 'jas']));
        $this->assertFalse(Str::doesntStartWith('jason', collect(['day', 'jas'])));
        $this->assertTrue(Str::doesntStartWith('jason', 'day'));
        $this->assertTrue(Str::doesntStartWith('jason', ['day']));
        $this->assertTrue(Str::doesntStartWith('jason', null));
        $this->assertTrue(Str::doesntStartWith('jason', [null]));
        $this->assertTrue(Str::doesntStartWith('0123', [null]));
        $this->assertFalse(Str::doesntStartWith('0123', 0));
        $this->assertTrue(Str::doesntStartWith('jason', 'J'));
        $this->assertTrue(Str::doesntStartWith('jason', ''));
        $this->assertTrue(Str::doesntStartWith('', ''));
        $this->assertTrue(Str::doesntStartWith('7', ' 7'));
        $this->assertFalse(Str::doesntStartWith('7a', '7'));
        $this->assertFalse(Str::doesntStartWith('7a', 7));
        $this->assertFalse(Str::doesntStartWith('7.12a', 7.12));
        $this->assertTrue(Str::doesntStartWith('7.12a', 7.13));
        $this->assertFalse(Str::doesntStartWith(7.123, '7'));
        $this->assertFalse(Str::doesntStartWith(7.123, '7.12'));
        $this->assertTrue(Str::doesntStartWith(7.123, '7.13'));
        $this->assertTrue(Str::doesntStartWith(null, 'Marc'));
        // Test for multibyte string support
        $this->assertFalse(Str::doesntStartWith('Jönköping', 'Jö'));
        $this->assertFalse(Str::doesntStartWith('Malmö', 'Malmö'));
        $this->assertTrue(Str::doesntStartWith('Jönköping', 'Jonko'));
        $this->assertTrue(Str::doesntStartWith('Malmö', 'Malmo'));
        $this->assertFalse(Str::doesntStartWith('你好', '你'));
        $this->assertTrue(Str::doesntStartWith('你好', '好'));
        $this->assertTrue(Str::doesntStartWith('你好', 'a'));
    }

    public function testEndsWith()
    {
        $this->assertTrue(Str::endsWith('jason', 'on'));
        $this->assertTrue(Str::endsWith('jason', 'jason'));
        $this->assertTrue(Str::endsWith('jason', ['on']));
        $this->assertTrue(Str::endsWith('jason', ['no', 'on']));
        $this->assertTrue(Str::endsWith('jason', collect(['no', 'on'])));
        $this->assertFalse(Str::endsWith('jason', 'no'));
        $this->assertFalse(Str::endsWith('jason', ['no']));
        $this->assertFalse(Str::endsWith('jason', ''));
        $this->assertFalse(Str::endsWith('', ''));
        $this->assertFalse(Str::endsWith('jason', [null]));
        $this->assertFalse(Str::endsWith('jason', null));
        $this->assertFalse(Str::endsWith('jason', 'N'));
        $this->assertFalse(Str::endsWith('7', ' 7'));
        $this->assertTrue(Str::endsWith('a7', '7'));
        $this->assertTrue(Str::endsWith('a7', 7));
        $this->assertTrue(Str::endsWith('a7.12', 7.12));
        $this->assertFalse(Str::endsWith('a7.12', 7.13));
        $this->assertTrue(Str::endsWith(0.27, '7'));
        $this->assertTrue(Str::endsWith(0.27, '0.27'));
        $this->assertFalse(Str::endsWith(0.27, '8'));
        $this->assertFalse(Str::endsWith(null, 'Marc'));
        // Test for multibyte string support
        $this->assertTrue(Str::endsWith('Jönköping', 'öping'));
        $this->assertTrue(Str::endsWith('Malmö', 'mö'));
        $this->assertFalse(Str::endsWith('Jönköping', 'oping'));
        $this->assertFalse(Str::endsWith('Malmö', 'mo'));
        $this->assertTrue(Str::endsWith('你好', '好'));
        $this->assertFalse(Str::endsWith('你好', '你'));
        $this->assertFalse(Str::endsWith('你好', 'a'));
    }

    public function testDoesntEndWith()
    {
        $this->assertFalse(Str::doesntEndWith('jason', 'on'));
        $this->assertFalse(Str::doesntEndWith('jason', 'jason'));
        $this->assertFalse(Str::doesntEndWith('jason', ['on']));
        $this->assertFalse(Str::doesntEndWith('jason', ['no', 'on']));
        $this->assertFalse(Str::doesntEndWith('jason', collect(['no', 'on'])));
        $this->assertTrue(Str::doesntEndWith('jason', 'no'));
        $this->assertTrue(Str::doesntEndWith('jason', ['no']));
        $this->assertTrue(Str::doesntEndWith('jason', ''));
        $this->assertTrue(Str::doesntEndWith('', ''));
        $this->assertTrue(Str::doesntEndWith('jason', [null]));
        $this->assertTrue(Str::doesntEndWith('jason', null));
        $this->assertTrue(Str::doesntEndWith('jason', 'N'));
        $this->assertTrue(Str::doesntEndWith('7', ' 7'));
        $this->assertFalse(Str::doesntEndWith('a7', '7'));
        $this->assertFalse(Str::doesntEndWith('a7', 7));
        $this->assertFalse(Str::doesntEndWith('a7.12', 7.12));
        $this->assertTrue(Str::doesntEndWith('a7.12', 7.13));
        $this->assertFalse(Str::doesntEndWith(0.27, '7'));
        $this->assertFalse(Str::doesntEndWith(0.27, '0.27'));
        $this->assertTrue(Str::doesntEndWith(0.27, '8'));
        $this->assertTrue(Str::doesntEndWith(null, 'Marc'));
        // Test for multibyte string support
        $this->assertFalse(Str::doesntEndWith('Jönköping', 'öping'));
        $this->assertFalse(Str::doesntEndWith('Malmö', 'mö'));
        $this->assertTrue(Str::doesntEndWith('Jönköping', 'oping'));
        $this->assertTrue(Str::doesntEndWith('Malmö', 'mo'));
        $this->assertFalse(Str::doesntEndWith('你好', '好'));
        $this->assertTrue(Str::doesntEndWith('你好', '你'));
        $this->assertTrue(Str::doesntEndWith('你好', 'a'));
    }

    public function testStrExcerpt()
    {
        $this->assertSame('...is a beautiful morn...', Str::excerpt('This is a beautiful morning', 'beautiful', ['radius' => 5]));
        $this->assertSame('This is a...', Str::excerpt('This is a beautiful morning', 'this', ['radius' => 5]));
        $this->assertSame('...iful morning', Str::excerpt('This is a beautiful morning', 'morning', ['radius' => 5]));
        $this->assertNull(Str::excerpt('This is a beautiful morning', 'day'));
        $this->assertSame('...is a beautiful! mor...', Str::excerpt('This is a beautiful! morning', 'Beautiful', ['radius' => 5]));
        $this->assertSame('...is a beautiful? mor...', Str::excerpt('This is a beautiful? morning', 'beautiful', ['radius' => 5]));
        $this->assertSame('', Str::excerpt('', '', ['radius' => 0]));
        $this->assertSame('a', Str::excerpt('a', 'a', ['radius' => 0]));
        $this->assertSame('...b...', Str::excerpt('abc', 'B', ['radius' => 0]));
        $this->assertSame('abc', Str::excerpt('abc', 'b', ['radius' => 1]));
        $this->assertSame('abc...', Str::excerpt('abcd', 'b', ['radius' => 1]));
        $this->assertSame('...abc', Str::excerpt('zabc', 'b', ['radius' => 1]));
        $this->assertSame('...abc...', Str::excerpt('zabcd', 'b', ['radius' => 1]));
        $this->assertSame('zabcd', Str::excerpt('zabcd', 'b', ['radius' => 2]));
        $this->assertSame('zabcd', Str::excerpt('  zabcd  ', 'b', ['radius' => 4]));
        $this->assertSame('...abc...', Str::excerpt('z  abc  d', 'b', ['radius' => 1]));
        $this->assertSame('[...]is a beautiful morn[...]', Str::excerpt('This is a beautiful morning', 'beautiful', ['omission' => '[...]', 'radius' => 5]));
        $this->assertSame(
            'This is the ultimate supercalifragilisticexpialidocious very looooooooooooooooooong looooooooooooong beautiful morning with amazing sunshine and awesome tempera[...]',
            Str::excerpt('This is the ultimate supercalifragilisticexpialidocious very looooooooooooooooooong looooooooooooong beautiful morning with amazing sunshine and awesome temperatures. So what are you gonna do about it?', 'very',
                ['omission' => '[...]'],
            ));

        $this->assertSame('...y...', Str::excerpt('taylor', 'y', ['radius' => 0]));
        $this->assertSame('...ayl...', Str::excerpt('taylor', 'Y', ['radius' => 1]));
        $this->assertSame('<div> The article description </div>', Str::excerpt('<div> The article description </div>', 'article'));
        $this->assertSame('...The article desc...', Str::excerpt('<div> The article description </div>', 'article', ['radius' => 5]));
        $this->assertSame('The article description', Str::excerpt(strip_tags('<div> The article description </div>'), 'article'));
        $this->assertSame('', Str::excerpt(null));
        $this->assertSame('', Str::excerpt(''));
        $this->assertSame('', Str::excerpt(null, ''));
        $this->assertSame('T...', Str::excerpt('The article description', null, ['radius' => 1]));
        $this->assertSame('The arti...', Str::excerpt('The article description', '', ['radius' => 8]));
        $this->assertSame('', Str::excerpt(' '));
        $this->assertSame('The arti...', Str::excerpt('The article description', ' ', ['radius' => 4]));
        $this->assertSame('...cle description', Str::excerpt('The article description', 'description', ['radius' => 4]));
        $this->assertSame('T...', Str::excerpt('The article description', 'T', ['radius' => 0]));
        $this->assertSame('What i?', Str::excerpt('What is the article?', 'What', ['radius' => 2, 'omission' => '?']));

        $this->assertSame('...ö - 二 sān 大åè...', Str::excerpt('åèö - 二 sān 大åèö', '二 sān', ['radius' => 4]));
        $this->assertSame('åèö - 二...', Str::excerpt('åèö - 二 sān 大åèö', 'åèö', ['radius' => 4]));
        $this->assertSame('åèö - 二 sān 大åèö', Str::excerpt('åèö - 二 sān 大åèö', 'åèö - 二 sān 大åèö', ['radius' => 4]));
        $this->assertSame('åèö - 二 sān 大åèö', Str::excerpt('åèö - 二 sān 大åèö', 'åèö - 二 sān 大åèö', ['radius' => 4]));
        $this->assertSame('...༼...', Str::excerpt('㏗༼㏗', '༼', ['radius' => 0]));
        $this->assertSame('...༼...', Str::excerpt('㏗༼㏗', '༼', ['radius' => 0]));
        $this->assertSame('...ocê e...', Str::excerpt('Como você está', 'ê', ['radius' => 2]));
        $this->assertSame('...ocê e...', Str::excerpt('Como você está', 'Ê', ['radius' => 2]));
        $this->assertSame('João...', Str::excerpt('João Antônio ', 'jo', ['radius' => 2]));
        $this->assertSame('João Antô...', Str::excerpt('João Antônio', 'JOÃO', ['radius' => 5]));
        $this->assertNull(Str::excerpt('', '/'));
    }

    public function testStrBefore(): void
    {
        $this->assertSame('han', Str::before('hannah', 'nah'));
        $this->assertSame('ha', Str::before('hannah', 'n'));
        $this->assertSame('ééé ', Str::before('ééé hannah', 'han'));
        $this->assertSame('hannah', Str::before('hannah', 'xxxx'));
        $this->assertSame('hannah', Str::before('hannah', ''));
        $this->assertSame('han', Str::before('han0nah', '0'));
        $this->assertSame('han', Str::before('han0nah', 0));
        $this->assertSame('han', Str::before('han2nah', 2));
        $this->assertSame('', Str::before('', ''));
        $this->assertSame('', Str::before('', 'a'));
        $this->assertSame('', Str::before('a', 'a'));
        $this->assertSame('foo', Str::before('foo@bar.com', '@'));
        $this->assertSame('foo', Str::before('foo@@bar.com', '@'));
        $this->assertSame('', Str::before('@foo@bar.com', '@'));
    }

    public function testStrBeforeLast(): void
    {
        $this->assertSame('yve', Str::beforeLast('yvette', 'tte'));
        $this->assertSame('yvet', Str::beforeLast('yvette', 't'));
        $this->assertSame('ééé ', Str::beforeLast('ééé yvette', 'yve'));
        $this->assertSame('', Str::beforeLast('yvette', 'yve'));
        $this->assertSame('yvette', Str::beforeLast('yvette', 'xxxx'));
        $this->assertSame('yvette', Str::beforeLast('yvette', ''));
        $this->assertSame('yv0et', Str::beforeLast('yv0et0te', '0'));
        $this->assertSame('yv0et', Str::beforeLast('yv0et0te', 0));
        $this->assertSame('yv2et', Str::beforeLast('yv2et2te', 2));
        $this->assertSame('', Str::beforeLast('', 'test'));
        $this->assertSame('', Str::beforeLast('yvette', 'yvette'));
        $this->assertSame('laravel', Str::beforeLast('laravel framework', ' '));
        $this->assertSame('yvette', Str::beforeLast("yvette\tyv0et0te", "\t"));
    }

    public function testStrBetween(): void
    {
        $this->assertSame('abc', Str::between('abc', '', 'c'));
        $this->assertSame('abc', Str::between('abc', 'a', ''));
        $this->assertSame('abc', Str::between('abc', '', ''));
        $this->assertSame('b', Str::between('abc', 'a', 'c'));
        $this->assertSame('b', Str::between('dddabc', 'a', 'c'));
        $this->assertSame('b', Str::between('abcddd', 'a', 'c'));
        $this->assertSame('b', Str::between('dddabcddd', 'a', 'c'));
        $this->assertSame('nn', Str::between('hannah', 'ha', 'ah'));
        $this->assertSame('a]ab[b', Str::between('[a]ab[b]', '[', ']'));
        $this->assertSame('foo', Str::between('foofoobar', 'foo', 'bar'));
        $this->assertSame('bar', Str::between('foobarbar', 'foo', 'bar'));
        $this->assertSame('234', Str::between('12345', 1, 5));
        $this->assertSame('45', Str::between('123456789', '123', '6789'));
        $this->assertSame('nothing', Str::between('nothing', 'foo', 'bar'));
    }

    public function testStrBetweenFirst()
    {
        $this->assertSame('abc', Str::betweenFirst('abc', '', 'c'));
        $this->assertSame('abc', Str::betweenFirst('abc', 'a', ''));
        $this->assertSame('abc', Str::betweenFirst('abc', '', ''));
        $this->assertSame('b', Str::betweenFirst('abc', 'a', 'c'));
        $this->assertSame('b', Str::betweenFirst('dddabc', 'a', 'c'));
        $this->assertSame('b', Str::betweenFirst('abcddd', 'a', 'c'));
        $this->assertSame('b', Str::betweenFirst('dddabcddd', 'a', 'c'));
        $this->assertSame('nn', Str::betweenFirst('hannah', 'ha', 'ah'));
        $this->assertSame('a', Str::betweenFirst('[a]ab[b]', '[', ']'));
        $this->assertSame('foo', Str::betweenFirst('foofoobar', 'foo', 'bar'));
        $this->assertSame('', Str::betweenFirst('foobarbar', 'foo', 'bar'));
    }

    public function testStrAfter()
    {
        $this->assertSame('nah', Str::after('hannah', 'han'));
        $this->assertSame('nah', Str::after('hannah', 'n'));
        $this->assertSame('nah', Str::after('ééé hannah', 'han'));
        $this->assertSame('hannah', Str::after('hannah', 'xxxx'));
        $this->assertSame('hannah', Str::after('hannah', ''));
        $this->assertSame('nah', Str::after('han0nah', '0'));
        $this->assertSame('nah', Str::after('han0nah', 0));
        $this->assertSame('nah', Str::after('han2nah', 2));
    }

    public function testStrAfterLast()
    {
        $this->assertSame('tte', Str::afterLast('yvette', 'yve'));
        $this->assertSame('e', Str::afterLast('yvette', 't'));
        $this->assertSame('e', Str::afterLast('ééé yvette', 't'));
        $this->assertSame('', Str::afterLast('yvette', 'tte'));
        $this->assertSame('yvette', Str::afterLast('yvette', 'xxxx'));
        $this->assertSame('yvette', Str::afterLast('yvette', ''));
        $this->assertSame('te', Str::afterLast('yv0et0te', '0'));
        $this->assertSame('te', Str::afterLast('yv0et0te', 0));
        $this->assertSame('te', Str::afterLast('yv2et2te', 2));
        $this->assertSame('foo', Str::afterLast('----foo', '---'));
    }

    #[DataProvider('strContainsProvider')]
    public function testStrContains($haystack, $needles, $expected, $ignoreCase = false)
    {
        $this->assertEquals($expected, Str::contains($haystack, $needles, $ignoreCase));
    }

    #[DataProvider('strContainsAllProvider')]
    public function testStrContainsAll($haystack, $needles, $expected, $ignoreCase = false)
    {
        $this->assertEquals($expected, Str::containsAll($haystack, $needles, $ignoreCase));
    }

    #[DataProvider('strDoesntContainProvider')]
    public function testStrDoesntContain($haystack, $needles, $expected, $ignoreCase = false)
    {
        $this->assertEquals($expected, Str::doesntContain($haystack, $needles, $ignoreCase));
    }

    public function testConvertCase()
    {
        // Upper Case Conversion
        $this->assertSame('HELLO', Str::convertCase('hello', MB_CASE_UPPER));
        $this->assertSame('WORLD', Str::convertCase('WORLD', MB_CASE_UPPER));

        // Lower Case Conversion
        $this->assertSame('hello', Str::convertCase('HELLO', MB_CASE_LOWER));
        $this->assertSame('world', Str::convertCase('WORLD', MB_CASE_LOWER));

        // Case Folding
        $this->assertSame('hello', Str::convertCase('HeLLo', MB_CASE_FOLD));
        $this->assertSame('world', Str::convertCase('WoRLD', MB_CASE_FOLD));

        // Multi-byte String
        $this->assertSame('ÜÖÄ', Str::convertCase('üöä', MB_CASE_UPPER, 'UTF-8'));
        $this->assertSame('üöä', Str::convertCase('ÜÖÄ', MB_CASE_LOWER, 'UTF-8'));

        // Unsupported Mode
        $this->expectException(\ValueError::class);
        Str::convertCase('Hello', -1);
    }

    public function testDedup()
    {
        $this->assertSame(' laravel php framework ', Str::deduplicate(' laravel   php  framework '));
        $this->assertSame('what', Str::deduplicate('whaaat', 'a'));
        $this->assertSame('/some/odd/path/', Str::deduplicate('/some//odd//path/', '/'));
        $this->assertSame('ムだム', Str::deduplicate('ムだだム', 'だ'));
        $this->assertSame(' laravel forever ', Str::deduplicate(' laravell    foreverrr  ', [' ', 'l', 'r']));
    }

    public function testParseCallback()
    {
        $this->assertEquals(['Class', 'method'], Str::parseCallback('Class@method'));
        $this->assertEquals(['Class', 'method'], Str::parseCallback('Class@method', 'foo'));
        $this->assertEquals(['Class', 'foo'], Str::parseCallback('Class', 'foo'));
        $this->assertEquals(['Class', null], Str::parseCallback('Class'));

        $this->assertEquals(["Class@anonymous\0/laravel/382.php:8$2ec", 'method'], Str::parseCallback("Class@anonymous\0/laravel/382.php:8$2ec@method"));
        $this->assertEquals(["Class@anonymous\0/laravel/382.php:8$2ec", 'method'], Str::parseCallback("Class@anonymous\0/laravel/382.php:8$2ec@method", 'foo'));
        $this->assertEquals(["Class@anonymous\0/laravel/382.php:8$2ec", 'foo'], Str::parseCallback("Class@anonymous\0/laravel/382.php:8$2ec", 'foo'));
        $this->assertEquals(["Class@anonymous\0/laravel/382.php:8$2ec", null], Str::parseCallback("Class@anonymous\0/laravel/382.php:8$2ec"));
    }

    public function testSlug()
    {
        $this->assertSame('hello-world', Str::slug('hello world'));
        $this->assertSame('hello-world', Str::slug('hello-world'));
        $this->assertSame('hello-world', Str::slug('hello_world'));
        $this->assertSame('hello_world', Str::slug('hello_world', '_'));
        $this->assertSame('user-at-host', Str::slug('user@host'));
        $this->assertSame('سلام-دنیا', Str::slug('سلام دنیا', '-', null));
        $this->assertSame('sometext', Str::slug('some text', ''));
        $this->assertSame('', Str::slug('', ''));
        $this->assertSame('', Str::slug(''));
        $this->assertSame('bsm-allah', Str::slug('بسم الله', '-', 'en', ['allh' => 'allah']));
        $this->assertSame('500-dollar-bill', Str::slug('500$ bill', '-', 'en', ['$' => 'dollar']));
        $this->assertSame('500-dollar-bill', Str::slug('500--$----bill', '-', 'en', ['$' => 'dollar']));
        $this->assertSame('500-dollar-bill', Str::slug('500-$-bill', '-', 'en', ['$' => 'dollar']));
        $this->assertSame('500-dollar-bill', Str::slug('500$--bill', '-', 'en', ['$' => 'dollar']));
        $this->assertSame('500-dollar-bill', Str::slug('500-$--bill', '-', 'en', ['$' => 'dollar']));
        $this->assertSame('أحمد-في-المدرسة', Str::slug('أحمد@المدرسة', '-', null, ['@' => 'في']));
    }

    public function testStrStart()
    {
        $this->assertSame('/test/string', Str::start('test/string', '/'));
        $this->assertSame('/test/string', Str::start('/test/string', '/'));
        $this->assertSame('/test/string', Str::start('//test/string', '/'));
    }

    public function testFlushCache()
    {
        $reflection = new ReflectionClass(Str::class);
        $property = $reflection->getProperty('snakeCache');

        Str::flushCache();
        $this->assertEmpty($property->getValue());

        Str::snake('Taylor Otwell');
        $this->assertNotEmpty($property->getValue());

        Str::flushCache();
        $this->assertEmpty($property->getValue());
    }

    public function testFinish()
    {
        $this->assertSame('abbc', Str::finish('ab', 'bc'));
        $this->assertSame('abbc', Str::finish('abbcbc', 'bc'));
        $this->assertSame('abcbbc', Str::finish('abcbbcbc', 'bc'));
    }

    public function testWrap()
    {
        $this->assertEquals('"value"', Str::wrap('value', '"'));
        $this->assertEquals('foo-bar-baz', Str::wrap('-bar-', 'foo', 'baz'));
    }

    public function testUnwrap()
    {
        $this->assertEquals('value', Str::unwrap('"value"', '"'));
        $this->assertEquals('value', Str::unwrap('"value', '"'));
        $this->assertEquals('value', Str::unwrap('value"', '"'));
        $this->assertEquals('bar', Str::unwrap('foo-bar-baz', 'foo-', '-baz'));
        $this->assertEquals('some: "json"', Str::unwrap('{some: "json"}', '{', '}'));
    }

    public function testIs()
    {
        $this->assertTrue(Str::is('/', '/'));
        $this->assertFalse(Str::is('/', ' /'));
        $this->assertFalse(Str::is('/', '/a'));
        $this->assertTrue(Str::is('foo/*', 'foo/bar/baz'));

        $this->assertTrue(Str::is('*@*', 'App\Class@method'));
        $this->assertTrue(Str::is('*@*', 'app\Class@'));
        $this->assertTrue(Str::is('*@*', '@method'));

        // is case sensitive
        $this->assertFalse(Str::is('*BAZ*', 'foo/bar/baz'));
        $this->assertFalse(Str::is('*FOO*', 'foo/bar/baz'));
        $this->assertFalse(Str::is('A', 'a'));

        // is not case sensitive
        $this->assertTrue(Str::is('A', 'a', true));
        $this->assertTrue(Str::is('*BAZ*', 'foo/bar/baz', true));
        $this->assertTrue(Str::is(['A*', 'B*'], 'a/', true));
        $this->assertFalse(Str::is(['A*', 'B*'], 'f/', true));
        $this->assertTrue(Str::is('FOO', 'foo', true));
        $this->assertTrue(Str::is('*FOO*', 'foo/bar/baz', true));
        $this->assertTrue(Str::is('foo/*', 'FOO/bar', true));

        // Accepts array of patterns
        $this->assertTrue(Str::is(['a*', 'b*'], 'a/'));
        $this->assertTrue(Str::is(['a*', 'b*'], 'b/'));
        $this->assertFalse(Str::is(['a*', 'b*'], 'f/'));

        // numeric values and patterns
        $this->assertFalse(Str::is(['a*', 'b*'], 123));
        $this->assertTrue(Str::is(['*2*', 'b*'], 11211));

        $this->assertTrue(Str::is('*/foo', 'blah/baz/foo'));

        $valueObject = new StringableObjectStub('foo/bar/baz');
        $patternObject = new StringableObjectStub('foo/*');

        $this->assertTrue(Str::is('foo/bar/baz', $valueObject));
        $this->assertTrue(Str::is($patternObject, $valueObject));

        // empty patterns
        $this->assertFalse(Str::is([], 'test'));

        $this->assertFalse(Str::is('', 0));
        $this->assertFalse(Str::is([null], 0));
        $this->assertTrue(Str::is([null], null));
    }

    public function testIsWithMultilineStrings()
    {
        $this->assertFalse(Str::is('/', "/\n"));
        $this->assertTrue(Str::is('/*', "/\n"));
        $this->assertTrue(Str::is('*/*', "/\n"));
        $this->assertTrue(Str::is('*/*', "\n/\n"));

        $this->assertTrue(Str::is('*', "\n"));
        $this->assertTrue(Str::is('*', "\n\n"));
        $this->assertFalse(Str::is('', "\n"));
        $this->assertFalse(Str::is('', "\n\n"));

        $multilineValue = <<<'VALUE'
        <?php

        namespace Illuminate\Tests\Support;

        use Exception;
        VALUE;

        $this->assertTrue(Str::is($multilineValue, $multilineValue));
        $this->assertTrue(Str::is('*', $multilineValue));
        $this->assertTrue(Str::is("*namespace Illuminate\Tests\*", $multilineValue));
        $this->assertFalse(Str::is("namespace Illuminate\Tests\*", $multilineValue));
        $this->assertFalse(Str::is("*namespace Illuminate\Tests", $multilineValue));
        $this->assertTrue(Str::is('<?php*', $multilineValue));
        $this->assertTrue(Str::is("<?php*namespace Illuminate\Tests\*", $multilineValue));
        $this->assertFalse(Str::is('use Exception;', $multilineValue));
        $this->assertFalse(Str::is('use Exception;*', $multilineValue));
        $this->assertTrue(Str::is('*use Exception;', $multilineValue));

        $this->assertTrue(Str::is("<?php\n\nnamespace Illuminate\Tests\*", $multilineValue));

        $this->assertTrue(Str::is(<<<'PATTERN'
        <?php
        *
        namespace Illuminate\Tests\*
        PATTERN, $multilineValue));

        $this->assertTrue(Str::is(<<<'PATTERN'
        <?php

        namespace Illuminate\Tests\*
        PATTERN, $multilineValue));
    }

    public function testIsUrl()
    {
        $this->assertTrue(Str::isUrl('https://laravel.com'));
        $this->assertTrue(Str::isUrl('http://localhost'));
        $this->assertFalse(Str::isUrl('invalid url'));
    }

    #[DataProvider('validUuidList')]
    public function testIsUuidWithValidUuid($uuid)
    {
        $this->assertTrue(Str::isUuid($uuid));
    }

    #[DataProvider('invalidUuidList')]
    public function testIsUuidWithInvalidUuid($uuid)
    {
        $this->assertFalse(Str::isUuid($uuid));
    }

    #[DataProvider('uuidVersionList')]
    public function testIsUuidWithVersion($uuid, $version, $passes)
    {
        $this->assertSame(Str::isUuid($uuid, $version), $passes);
    }

    public function testIsJson()
    {
        $this->assertTrue(Str::isJson('1'));
        $this->assertTrue(Str::isJson('[1,2,3]'));
        $this->assertTrue(Str::isJson('[1,   2,   3]'));
        $this->assertTrue(Str::isJson('{"first": "John", "last": "Doe"}'));
        $this->assertTrue(Str::isJson('[{"first": "John", "last": "Doe"}, {"first": "Jane", "last": "Doe"}]'));

        $this->assertFalse(Str::isJson('1,'));
        $this->assertFalse(Str::isJson('[1,2,3'));
        $this->assertFalse(Str::isJson('[1,   2   3]'));
        $this->assertFalse(Str::isJson('{first: "John"}'));
        $this->assertFalse(Str::isJson('[{first: "John"}, {first: "Jane"}]'));
        $this->assertFalse(Str::isJson(''));
        $this->assertFalse(Str::isJson(null));
        $this->assertFalse(Str::isJson([]));
    }

    public function testIsMatch()
    {
        $this->assertTrue(Str::isMatch('/.*,.*!/', 'Hello, Laravel!'));
        $this->assertTrue(Str::isMatch('/^.*$(.*)/', 'Hello, Laravel!'));
        $this->assertTrue(Str::isMatch('/laravel/i', 'Hello, Laravel!'));
        $this->assertTrue(Str::isMatch('/^(.*(.*(.*)))/', 'Hello, Laravel!'));

        $this->assertFalse(Str::isMatch('/H.o/', 'Hello, Laravel!'));
        $this->assertFalse(Str::isMatch('/^laravel!/i', 'Hello, Laravel!'));
        $this->assertFalse(Str::isMatch('/laravel!(.*)/', 'Hello, Laravel!'));
        $this->assertFalse(Str::isMatch('/^[a-zA-Z,!]+$/', 'Hello, Laravel!'));

        $this->assertTrue(Str::isMatch(['/.*,.*!/', '/H.o/'], 'Hello, Laravel!'));
        $this->assertTrue(Str::isMatch(['/^laravel!/i', '/^.*$(.*)/'], 'Hello, Laravel!'));
        $this->assertTrue(Str::isMatch(['/laravel/i', '/laravel!(.*)/'], 'Hello, Laravel!'));
        $this->assertTrue(Str::isMatch(['/^[a-zA-Z,!]+$/', '/^(.*(.*(.*)))/'], 'Hello, Laravel!'));
    }

    public function testKebab()
    {
        $this->assertSame('laravel-php-framework', Str::kebab('LaravelPhpFramework'));
        $this->assertSame('laravel-php-framework', Str::kebab('Laravel Php Framework'));
        $this->assertSame('laravel❤-php-framework', Str::kebab('Laravel ❤ Php Framework'));
        $this->assertSame('', Str::kebab(''));
    }

    public function testLower()
    {
        $this->assertSame('foo bar baz', Str::lower('FOO BAR BAZ'));
        $this->assertSame('foo bar baz', Str::lower('fOo Bar bAz'));
    }

    public function testUpper()
    {
        $this->assertSame('FOO BAR BAZ', Str::upper('foo bar baz'));
        $this->assertSame('FOO BAR BAZ', Str::upper('foO bAr BaZ'));
    }

    public function testLimit()
    {
        $this->assertSame('Laravel is...', Str::limit('Laravel is a free, open source PHP web application framework.', 10));
        $this->assertSame('这是一...', Str::limit('这是一段中文', 6));
        $this->assertSame('Laravel is a...', Str::limit('Laravel is a free, open source PHP web application framework.', 15, preserveWords: true));

        $string = 'The PHP framework for web artisans.';
        $this->assertSame('The PHP...', Str::limit($string, 7));
        $this->assertSame('The PHP...', Str::limit($string, 10, preserveWords: true));
        $this->assertSame('The PHP', Str::limit($string, 7, ''));
        $this->assertSame('The PHP', Str::limit($string, 10, '', true));
        $this->assertSame('The PHP framework for web artisans.', Str::limit($string, 100));
        $this->assertSame('The PHP framework for web artisans.', Str::limit($string, 100, preserveWords: true));
        $this->assertSame('The PHP framework...', Str::limit($string, 20, preserveWords: true));

        $nonAsciiString = '这是一段中文';
        $this->assertSame('这是一...', Str::limit($nonAsciiString, 6));
        $this->assertSame('这是一...', Str::limit($nonAsciiString, 6, preserveWords: true));
        $this->assertSame('这是一', Str::limit($nonAsciiString, 6, ''));
        $this->assertSame('这是一', Str::limit($nonAsciiString, 6, '', true));
    }

    public function testLength()
    {
        $this->assertEquals(11, Str::length('foo bar baz'));
        $this->assertEquals(11, Str::length('foo bar baz', 'UTF-8'));
    }

    public function testNumbers()
    {
        $this->assertSame('5551234567', Str::numbers('(555) 123-4567'));
        $this->assertSame('443', Str::numbers('L4r4v3l!'));
        $this->assertSame('', Str::numbers('Laravel!'));

        $arrayValue = ['(555) 123-4567', 'L4r4v3l', 'Laravel!'];
        $arrayExpected = ['5551234567', '443', ''];
        $this->assertSame($arrayExpected, Str::numbers($arrayValue));
    }

    public function testRandom()
    {
        $this->assertEquals(16, strlen(Str::random()));
        $randomInteger = random_int(1, 100);
        $this->assertEquals($randomInteger, strlen(Str::random($randomInteger)));
        $this->assertIsString(Str::random());
    }

    public function testWhetherTheNumberOfGeneratedCharactersIsEquallyDistributed()
    {
        $results = [];
        // take 6.200.000 samples, because there are 62 different characters
        for ($i = 0; $i < 620000; $i++) {
            $random = Str::random(1);
            $results[$random] = ($results[$random] ?? 0) + 1;
        }

        // each character should occur 100.000 times with a variance of 5%.
        foreach ($results as $result) {
            $this->assertEqualsWithDelta(10000, $result, 500);
        }
    }

    public function testRandomStringFactoryCanBeSet()
    {
        Str::createRandomStringsUsing(fn ($length) => 'length:'.$length);

        $this->assertSame('length:7', Str::random(7));
        $this->assertSame('length:7', Str::random(7));

        Str::createRandomStringsNormally();

        $this->assertNotSame('length:7', Str::random());
    }

    public function testItCanSpecifyASequenceOfRandomStringsToUtilise()
    {
        Str::createRandomStringsUsingSequence([
            0 => 'x',
            // 1 => just generate a random one here...
            2 => 'y',
            3 => 'z',
            // ... => continue to generate random strings...
        ]);

        $this->assertSame('x', Str::random());
        $this->assertSame(16, mb_strlen(Str::random()));
        $this->assertSame('y', Str::random());
        $this->assertSame('z', Str::random());
        $this->assertSame(16, mb_strlen(Str::random()));
        $this->assertSame(16, mb_strlen(Str::random()));

        Str::createRandomStringsNormally();
    }

    public function testItCanSpecifyAFallbackForARandomStringSequence()
    {
        Str::createRandomStringsUsingSequence([Str::random(), Str::random()], fn () => throw new Exception('Out of random strings.'));
        Str::random();
        Str::random();

        try {
            $this->expectExceptionMessage('Out of random strings.');
            Str::random();
            $this->fail();
        } finally {
            Str::createRandomStringsNormally();
        }
    }

    public function testReplace()
    {
        $this->assertSame('foo bar laravel', Str::replace('baz', 'laravel', 'foo bar baz'));
        $this->assertSame('foo bar laravel', Str::replace('baz', 'laravel', 'foo bar Baz', false));
        $this->assertSame('foo bar baz 8.x', Str::replace('?', '8.x', 'foo bar baz ?'));
        $this->assertSame('foo bar baz 8.x', Str::replace('x', '8.x', 'foo bar baz X', false));
        $this->assertSame('foo/bar/baz', Str::replace(' ', '/', 'foo bar baz'));
        $this->assertSame('foo bar baz', Str::replace(['?1', '?2', '?3'], ['foo', 'bar', 'baz'], '?1 ?2 ?3'));
        $this->assertSame(['foo', 'bar', 'baz'], Str::replace(collect(['?1', '?2', '?3']), collect(['foo', 'bar', 'baz']), collect(['?1', '?2', '?3'])));
    }

    public function testReplaceArray()
    {
        $this->assertSame('foo/bar/baz', Str::replaceArray('?', ['foo', 'bar', 'baz'], '?/?/?'));
        $this->assertSame('foo/bar/baz/?', Str::replaceArray('?', ['foo', 'bar', 'baz'], '?/?/?/?'));
        $this->assertSame('foo/bar', Str::replaceArray('?', ['foo', 'bar', 'baz'], '?/?'));
        $this->assertSame('?/?/?', Str::replaceArray('x', ['foo', 'bar', 'baz'], '?/?/?'));
        // Ensure recursive replacements are avoided
        $this->assertSame('foo?/bar/baz', Str::replaceArray('?', ['foo?', 'bar', 'baz'], '?/?/?'));
        // Test for associative array support
        $this->assertSame('foo/bar', Str::replaceArray('?', [1 => 'foo', 2 => 'bar'], '?/?'));
        $this->assertSame('foo/bar', Str::replaceArray('?', ['x' => 'foo', 'y' => 'bar'], '?/?'));
        // Test does not crash on bad input
        $this->assertSame('?', Str::replaceArray('?', [(object) ['foo' => 'bar']], '?'));
    }

    public function testReplaceFirst()
    {
        $this->assertSame('fooqux foobar', Str::replaceFirst('bar', 'qux', 'foobar foobar'));
        $this->assertSame('foo/qux? foo/bar?', Str::replaceFirst('bar?', 'qux?', 'foo/bar? foo/bar?'));
        $this->assertSame('foo foobar', Str::replaceFirst('bar', '', 'foobar foobar'));
        $this->assertSame('foobar foobar', Str::replaceFirst('xxx', 'yyy', 'foobar foobar'));
        $this->assertSame('foobar foobar', Str::replaceFirst('', 'yyy', 'foobar foobar'));
        $this->assertSame('1', Str::replaceFirst(0, '1', '0'));
        // Test for multibyte string support
        $this->assertSame('Jxxxnköping Malmö', Str::replaceFirst('ö', 'xxx', 'Jönköping Malmö'));
        $this->assertSame('Jönköping Malmö', Str::replaceFirst('', 'yyy', 'Jönköping Malmö'));
    }

    public function testReplaceStart()
    {
        $this->assertSame('foobar foobar', Str::replaceStart('bar', 'qux', 'foobar foobar'));
        $this->assertSame('foo/bar? foo/bar?', Str::replaceStart('bar?', 'qux?', 'foo/bar? foo/bar?'));
        $this->assertSame('quxbar foobar', Str::replaceStart('foo', 'qux', 'foobar foobar'));
        $this->assertSame('qux? foo/bar?', Str::replaceStart('foo/bar?', 'qux?', 'foo/bar? foo/bar?'));
        $this->assertSame('bar foobar', Str::replaceStart('foo', '', 'foobar foobar'));
        $this->assertSame('1', Str::replaceStart(0, '1', '0'));
        // Test for multibyte string support
        $this->assertSame('xxxnköping Malmö', Str::replaceStart('Jö', 'xxx', 'Jönköping Malmö'));
        $this->assertSame('Jönköping Malmö', Str::replaceStart('', 'yyy', 'Jönköping Malmö'));
    }

    public function testReplaceLast()
    {
        $this->assertSame('foobar fooqux', Str::replaceLast('bar', 'qux', 'foobar foobar'));
        $this->assertSame('foo/bar? foo/qux?', Str::replaceLast('bar?', 'qux?', 'foo/bar? foo/bar?'));
        $this->assertSame('foobar foo', Str::replaceLast('bar', '', 'foobar foobar'));
        $this->assertSame('foobar foobar', Str::replaceLast('xxx', 'yyy', 'foobar foobar'));
        $this->assertSame('foobar foobar', Str::replaceLast('', 'yyy', 'foobar foobar'));
        // Test for multibyte string support
        $this->assertSame('Malmö Jönkxxxping', Str::replaceLast('ö', 'xxx', 'Malmö Jönköping'));
        $this->assertSame('Malmö Jönköping', Str::replaceLast('', 'yyy', 'Malmö Jönköping'));
    }

    public function testReplaceEnd()
    {
        $this->assertSame('foobar fooqux', Str::replaceEnd('bar', 'qux', 'foobar foobar'));
        $this->assertSame('foo/bar? foo/qux?', Str::replaceEnd('bar?', 'qux?', 'foo/bar? foo/bar?'));
        $this->assertSame('foobar foo', Str::replaceEnd('bar', '', 'foobar foobar'));
        $this->assertSame('foobar foobar', Str::replaceEnd('xxx', 'yyy', 'foobar foobar'));
        $this->assertSame('foobar foobar', Str::replaceEnd('', 'yyy', 'foobar foobar'));
        $this->assertSame('fooxxx foobar', Str::replaceEnd('xxx', 'yyy', 'fooxxx foobar'));

        // // Test for multibyte string support
        $this->assertSame('Malmö Jönköping', Str::replaceEnd('ö', 'xxx', 'Malmö Jönköping'));
        $this->assertSame('Malmö Jönkyyy', Str::replaceEnd('öping', 'yyy', 'Malmö Jönköping'));
    }

    public function testRemove()
    {
        $this->assertSame('Fbar', Str::remove('o', 'Foobar'));
        $this->assertSame('Foo', Str::remove('bar', 'Foobar'));
        $this->assertSame('oobar', Str::remove('F', 'Foobar'));
        $this->assertSame('Foobar', Str::remove('f', 'Foobar'));
        $this->assertSame('oobar', Str::remove('f', 'Foobar', false));

        $this->assertSame('Fbr', Str::remove(['o', 'a'], 'Foobar'));
        $this->assertSame('Fooar', Str::remove(['f', 'b'], 'Foobar'));
        $this->assertSame('ooar', Str::remove(['f', 'b'], 'Foobar', false));
        $this->assertSame('Foobar', Str::remove(['f', '|'], 'Foo|bar'));
    }

    public function testReverse()
    {
        $this->assertSame('FooBar', Str::reverse('raBooF'));
        $this->assertSame('Teniszütő', Str::reverse('őtüzsineT'));
        $this->assertSame('❤MultiByte☆', Str::reverse('☆etyBitluM❤'));
    }

    public function testSnake()
    {
        $this->assertSame('laravel_p_h_p_framework', Str::snake('LaravelPHPFramework'));
        $this->assertSame('laravel_php_framework', Str::snake('LaravelPhpFramework'));
        $this->assertSame('laravel php framework', Str::snake('LaravelPhpFramework', ' '));
        $this->assertSame('laravel_php_framework', Str::snake('Laravel Php Framework'));
        $this->assertSame('laravel_php_framework', Str::snake('Laravel    Php      Framework   '));
        // ensure cache keys don't overlap
        $this->assertSame('laravel__php__framework', Str::snake('LaravelPhpFramework', '__'));
        $this->assertSame('laravel_php_framework_', Str::snake('LaravelPhpFramework_', '_'));
        $this->assertSame('laravel_php_framework', Str::snake('laravel php Framework'));
        $this->assertSame('laravel_php_frame_work', Str::snake('laravel php FrameWork'));
        // prevent breaking changes
        $this->assertSame('foo-bar', Str::snake('foo-bar'));
        $this->assertSame('foo-_bar', Str::snake('Foo-Bar'));
        $this->assertSame('foo__bar', Str::snake('Foo_Bar'));
        $this->assertSame('żółtałódka', Str::snake('ŻółtaŁódka'));
    }

    public function testTrim()
    {
        $this->assertSame('foo bar', Str::trim('   foo bar   '));
        $this->assertSame('foo bar', Str::trim('foo bar   '));
        $this->assertSame('foo bar', Str::trim('   foo bar'));
        $this->assertSame('foo bar', Str::trim('foo bar'));
        $this->assertSame(' foo bar ', Str::trim(' foo bar ', ''));
        $this->assertSame('foo bar', Str::trim(' foo bar ', ' '));
        $this->assertSame('foo  bar', Str::trim('-foo  bar_', '-_'));

        $this->assertSame('foo    bar', Str::trim(' foo    bar '));

        $this->assertSame('123', Str::trim('   123    '));
        $this->assertSame('だ', Str::trim('だ'));
        $this->assertSame('ム', Str::trim('ム'));
        $this->assertSame('だ', Str::trim('   だ    '));
        $this->assertSame('ム', Str::trim('   ム    '));

        $this->assertSame(
            'foo bar',
            Str::trim('
                foo bar
            ')
        );
        $this->assertSame(
            'foo
                bar',
            Str::trim('
                foo
                bar
            ')
        );

        $this->assertSame("\xE9", Str::trim(" \xE9 "));

        $trimDefaultChars = [' ', "\n", "\r", "\t", "\v", "\0"];

        foreach ($trimDefaultChars as $char) {
            $this->assertSame('', Str::trim(" {$char} "));
            $this->assertSame(trim(" {$char} "), Str::trim(" {$char} "));

            $this->assertSame('foo bar', Str::trim("{$char} foo bar {$char}"));
            $this->assertSame(trim("{$char} foo bar {$char}"), Str::trim("{$char} foo bar {$char}"));
        }
    }

    public function testLtrim()
    {
        $this->assertSame('foo    bar ', Str::ltrim(' foo    bar '));

        $this->assertSame('123    ', Str::ltrim('   123    '));
        $this->assertSame('だ', Str::ltrim('だ'));
        $this->assertSame('ム', Str::ltrim('ム'));
        $this->assertSame('だ    ', Str::ltrim('   だ    '));
        $this->assertSame('ム    ', Str::ltrim('   ム    '));

        $this->assertSame(
            'foo bar
            ',
            Str::ltrim('
                foo bar
            ')
        );
        $this->assertSame("\xE9 ", Str::ltrim(" \xE9 "));

        $ltrimDefaultChars = [' ', "\n", "\r", "\t", "\v", "\0"];

        foreach ($ltrimDefaultChars as $char) {
            $this->assertSame('', Str::ltrim(" {$char} "));
            $this->assertSame(ltrim(" {$char} "), Str::ltrim(" {$char} "));

            $this->assertSame("foo bar {$char}", Str::ltrim("{$char} foo bar {$char}"));
            $this->assertSame(ltrim("{$char} foo bar {$char}"), Str::ltrim("{$char} foo bar {$char}"));
        }
    }

    public function testRtrim()
    {
        $this->assertSame(' foo    bar', Str::rtrim(' foo    bar '));

        $this->assertSame('   123', Str::rtrim('   123    '));
        $this->assertSame('だ', Str::rtrim('だ'));
        $this->assertSame('ム', Str::rtrim('ム'));
        $this->assertSame('   だ', Str::rtrim('   だ    '));
        $this->assertSame('   ム', Str::rtrim('   ム    '));

        $this->assertSame(
            '
                foo bar',
            Str::rtrim('
                foo bar
            ')
        );

        $this->assertSame(" \xE9", Str::rtrim(" \xE9 "));

        $rtrimDefaultChars = [' ', "\n", "\r", "\t", "\v", "\0"];

        foreach ($rtrimDefaultChars as $char) {
            $this->assertSame('', Str::rtrim(" {$char} "));
            $this->assertSame(rtrim(" {$char} "), Str::rtrim(" {$char} "));

            $this->assertSame("{$char} foo bar", Str::rtrim("{$char} foo bar {$char}"));
            $this->assertSame(rtrim("{$char} foo bar {$char}"), Str::rtrim("{$char} foo bar {$char}"));
        }
    }

    public function testSquish()
    {
        $this->assertSame('laravel php framework', Str::squish(' laravel   php  framework '));
        $this->assertSame('laravel php framework', Str::squish("laravel\t\tphp\n\nframework"));
        $this->assertSame('laravel php framework', Str::squish('
            laravel
            php
            framework
        '));
        $this->assertSame('laravel php framework', Str::squish('   laravel   php   framework   '));
        $this->assertSame('123', Str::squish('   123    '));
        $this->assertSame('だ', Str::squish('だ'));
        $this->assertSame('ム', Str::squish('ム'));
        $this->assertSame('だ', Str::squish('   だ    '));
        $this->assertSame('ム', Str::squish('   ム    '));
        $this->assertSame('laravel php framework', Str::squish('laravelㅤㅤㅤphpㅤframework'));
        $this->assertSame('laravel php framework', Str::squish('laravelᅠᅠᅠᅠᅠᅠᅠᅠᅠᅠphpᅠᅠframework'));
    }

    public function testStudly()
    {
        $this->assertSame('LaravelPHPFramework', Str::studly('laravel_p_h_p_framework'));
        $this->assertSame('LaravelPhpFramework', Str::studly('laravel_php_framework'));
        $this->assertSame('LaravelPhPFramework', Str::studly('laravel-phP-framework'));
        $this->assertSame('LaravelPhpFramework', Str::studly('laravel  -_-  php   -_-   framework   '));

        $this->assertSame('FooBar', Str::studly('fooBar'));
        $this->assertSame('FooBar', Str::studly('foo_bar'));
        $this->assertSame('FooBar', Str::studly('foo_bar')); // test cache
        $this->assertSame('FooBarBaz', Str::studly('foo-barBaz'));
        $this->assertSame('FooBarBaz', Str::studly('foo-bar_baz'));

        $this->assertSame('ÖffentlicheÜberraschungen', Str::studly('öffentliche-überraschungen'));
    }

    public function testPascal()
    {
        $this->assertSame('LaravelPhpFramework', Str::pascal('laravel_php_framework'));
        $this->assertSame('LaravelPhpFramework', Str::pascal('laravel-php-framework'));
        $this->assertSame('LaravelPhpFramework', Str::pascal('laravel  -_-  php   -_-   framework   '));

        $this->assertSame('FooBar', Str::pascal('fooBar'));
        $this->assertSame('FooBar', Str::pascal('foo_bar'));
        $this->assertSame('FooBar', Str::pascal('foo_bar')); // test cache
        $this->assertSame('FooBarBaz', Str::pascal('foo-barBaz'));
        $this->assertSame('FooBarBaz', Str::pascal('foo-bar_baz'));

        $this->assertSame('ÖffentlicheÜberraschungen', Str::pascal('öffentliche-überraschungen'));
    }

    public function testMask()
    {
        $this->assertSame('tay*************', Str::mask('taylor@email.com', '*', 3));
        $this->assertSame('******@email.com', Str::mask('taylor@email.com', '*', 0, 6));
        $this->assertSame('tay*************', Str::mask('taylor@email.com', '*', -13));
        $this->assertSame('tay***@email.com', Str::mask('taylor@email.com', '*', -13, 3));

        $this->assertSame('****************', Str::mask('taylor@email.com', '*', -17));
        $this->assertSame('*****r@email.com', Str::mask('taylor@email.com', '*', -99, 5));

        $this->assertSame('taylor@email.com', Str::mask('taylor@email.com', '*', 16));
        $this->assertSame('taylor@email.com', Str::mask('taylor@email.com', '*', 16, 99));

        $this->assertSame('taylor@email.com', Str::mask('taylor@email.com', '', 3));

        $this->assertSame('taysssssssssssss', Str::mask('taylor@email.com', 'something', 3));
        $this->assertSame('taysssssssssssss', Str::mask('taylor@email.com', Str::of('something'), 3));

        $this->assertSame('这是一***', Str::mask('这是一段中文', '*', 3));
        $this->assertSame('**一段中文', Str::mask('这是一段中文', '*', 0, 2));

        $this->assertSame('ma*n@email.com', Str::mask('maan@email.com', '*', 2, 1));
        $this->assertSame('ma***email.com', Str::mask('maan@email.com', '*', 2, 3));
        $this->assertSame('ma************', Str::mask('maan@email.com', '*', 2));

        $this->assertSame('mari*@email.com', Str::mask('maria@email.com', '*', 4, 1));
        $this->assertSame('tamar*@email.com', Str::mask('tamara@email.com', '*', 5, 1));

        $this->assertSame('*aria@email.com', Str::mask('maria@email.com', '*', 0, 1));
        $this->assertSame('maria@email.co*', Str::mask('maria@email.com', '*', -1, 1));
        $this->assertSame('maria@email.co*', Str::mask('maria@email.com', '*', -1));
        $this->assertSame('***************', Str::mask('maria@email.com', '*', -15));
        $this->assertSame('***************', Str::mask('maria@email.com', '*', 0));
    }

    public function testMatch(): void
    {
        $this->assertSame('bar', Str::match('/bar/', 'foo bar'));
        $this->assertSame('bar', Str::match('/foo (.*)/', 'foo bar'));
        $this->assertEmpty(Str::match('/nothing/', 'foo bar'));

        $this->assertEquals(['bar', 'bar'], Str::matchAll('/bar/', 'bar foo bar')->all());

        $this->assertEquals(['un', 'ly'], Str::matchAll('/f(\w*)/', 'bar fun bar fly')->all());
        $this->assertEmpty(Str::matchAll('/nothing/', 'bar fun bar fly'));

        $this->assertEmpty(Str::match('/pattern/', ''));
        $this->assertEmpty(Str::matchAll('/pattern/', ''));
    }

    public function testCamel(): void
    {
        $this->assertSame('laravelPHPFramework', Str::camel('Laravel_p_h_p_framework'));
        $this->assertSame('laravelPhpFramework', Str::camel('Laravel_php_framework'));
        $this->assertSame('laravelPhPFramework', Str::camel('Laravel-phP-framework'));
        $this->assertSame('laravelPhpFramework', Str::camel('Laravel  -_-  php   -_-   framework   '));

        $this->assertSame('fooBar', Str::camel('FooBar'));
        $this->assertSame('fooBar', Str::camel('foo_bar'));
        $this->assertSame('fooBar', Str::camel('foo_bar')); // test cache
        $this->assertSame('fooBarBaz', Str::camel('Foo-barBaz'));
        $this->assertSame('fooBarBaz', Str::camel('foo-bar_baz'));

        $this->assertSame('', Str::camel(''));
        $this->assertSame('lARAVELPHPFRAMEWORK', Str::camel('LARAVEL_PHP_FRAMEWORK'));
        $this->assertSame('laravelPhpFramework', Str::camel('   laravel   php   framework   '));

        $this->assertSame('foo1Bar', Str::camel('foo1_bar'));
        $this->assertSame('1FooBar', Str::camel('1 foo bar'));
    }

    public function testCharAt()
    {
        $this->assertEquals('р', Str::charAt('Привет, мир!', 1));
        $this->assertEquals('ち', Str::charAt('「こんにちは世界」', 4));
        $this->assertEquals('w', Str::charAt('Привет, world!', 8));
        $this->assertEquals('界', Str::charAt('「こんにちは世界」', -2));
        $this->assertEquals(null, Str::charAt('「こんにちは世界」', -200));
        $this->assertEquals(null, Str::charAt('Привет, мир!', 100));
    }

    public function testSubstr()
    {
        $this->assertSame('Ё', Str::substr('БГДЖИЛЁ', -1));
        $this->assertSame('ЛЁ', Str::substr('БГДЖИЛЁ', -2));
        $this->assertSame('И', Str::substr('БГДЖИЛЁ', -3, 1));
        $this->assertSame('ДЖИЛ', Str::substr('БГДЖИЛЁ', 2, -1));
        $this->assertEmpty(Str::substr('БГДЖИЛЁ', 4, -4));
        $this->assertSame('ИЛ', Str::substr('БГДЖИЛЁ', -3, -1));
        $this->assertSame('ГДЖИЛЁ', Str::substr('БГДЖИЛЁ', 1));
        $this->assertSame('ГДЖ', Str::substr('БГДЖИЛЁ', 1, 3));
        $this->assertSame('БГДЖ', Str::substr('БГДЖИЛЁ', 0, 4));
        $this->assertSame('Ё', Str::substr('БГДЖИЛЁ', -1, 1));
        $this->assertEmpty(Str::substr('Б', 2));
    }

    public function testSubstrCount()
    {
        $this->assertSame(3, Str::substrCount('laravelPHPFramework', 'a'));
        $this->assertSame(0, Str::substrCount('laravelPHPFramework', 'z'));
        $this->assertSame(1, Str::substrCount('laravelPHPFramework', 'l', 2));
        $this->assertSame(0, Str::substrCount('laravelPHPFramework', 'z', 2));
        $this->assertSame(1, Str::substrCount('laravelPHPFramework', 'k', -1));
        $this->assertSame(1, Str::substrCount('laravelPHPFramework', 'k', -1));
        $this->assertSame(1, Str::substrCount('laravelPHPFramework', 'a', 1, 2));
        $this->assertSame(1, Str::substrCount('laravelPHPFramework', 'a', 1, 2));
        $this->assertSame(3, Str::substrCount('laravelPHPFramework', 'a', 1, -2));
        $this->assertSame(1, Str::substrCount('laravelPHPFramework', 'a', -10, -3));
    }

    public function testPosition()
    {
        $this->assertSame(7, Str::position('Hello, World!', 'W'));
        $this->assertSame(10, Str::position('This is a test string.', 'test'));
        $this->assertSame(23, Str::position('This is a test string, test again.', 'test', 15));
        $this->assertSame(0, Str::position('Hello, World!', 'Hello'));
        $this->assertSame(7, Str::position('Hello, World!', 'World!'));
        $this->assertSame(10, Str::position('This is a tEsT string.', 'tEsT', 0, 'UTF-8'));
        $this->assertSame(7, Str::position('Hello, World!', 'W', -6));
        $this->assertSame(18, Str::position('Äpfel, Birnen und Kirschen', 'Kirschen', -10, 'UTF-8'));
        $this->assertSame(9, Str::position('@%€/=!"][$', '$', 0, 'UTF-8'));
        $this->assertFalse(Str::position('Hello, World!', 'w', 0, 'UTF-8'));
        $this->assertFalse(Str::position('Hello, World!', 'X', 0, 'UTF-8'));
        $this->assertFalse(Str::position('', 'test'));
        $this->assertFalse(Str::position('Hello, World!', 'X'));
    }

    public function testSubstrReplace()
    {
        $this->assertSame('12:00', Str::substrReplace('1200', ':', 2, 0));
        $this->assertSame('The Laravel Framework', Str::substrReplace('The Framework', 'Laravel ', 4, 0));
        $this->assertSame('Laravel – The PHP Framework for Web Artisans', Str::substrReplace('Laravel Framework', '– The PHP Framework for Web Artisans', 8));
    }

    public function testTake()
    {
        $this->assertSame('ab', Str::take('abcdef', 2));
        $this->assertSame('ef', Str::take('abcdef', -2));
        $this->assertSame('', Str::take('abcdef', 0));
        $this->assertSame('', Str::take('', 2));
        $this->assertSame('abcdef', Str::take('abcdef', 10));
        $this->assertSame('abcdef', Str::take('abcdef', 6));
        $this->assertSame('ü', Str::take('üöä', 1));
    }

    public function testLcfirst()
    {
        $this->assertSame('laravel', Str::lcfirst('Laravel'));
        $this->assertSame('laravel framework', Str::lcfirst('Laravel framework'));
        $this->assertSame('мама', Str::lcfirst('Мама'));
        $this->assertSame('мама мыла раму', Str::lcfirst('Мама мыла раму'));
    }

    public function testUcfirst()
    {
        $this->assertSame('Laravel', Str::ucfirst('laravel'));
        $this->assertSame('Laravel framework', Str::ucfirst('laravel framework'));
        $this->assertSame('Мама', Str::ucfirst('мама'));
        $this->assertSame('Мама мыла раму', Str::ucfirst('мама мыла раму'));
    }

    public function testUcsplit()
    {
        $this->assertSame(['Laravel_p_h_p_framework'], Str::ucsplit('Laravel_p_h_p_framework'));
        $this->assertSame(['Laravel_', 'P_h_p_framework'], Str::ucsplit('Laravel_P_h_p_framework'));
        $this->assertSame(['laravel', 'P', 'H', 'P', 'Framework'], Str::ucsplit('laravelPHPFramework'));
        $this->assertSame(['Laravel-ph', 'P-framework'], Str::ucsplit('Laravel-phP-framework'));

        $this->assertSame(['Żółta', 'Łódka'], Str::ucsplit('ŻółtaŁódka'));
        $this->assertSame(['sind', 'Öde', 'Und', 'So'], Str::ucsplit('sindÖdeUndSo'));
        $this->assertSame(['Öffentliche', 'Überraschungen'], Str::ucsplit('ÖffentlicheÜberraschungen'));
    }

    public function testUuid()
    {
        $this->assertInstanceOf(UuidInterface::class, Str::uuid());
        $this->assertInstanceOf(UuidInterface::class, Str::orderedUuid());
        $this->assertInstanceOf(UuidInterface::class, Str::uuid7());
    }

    public function testAsciiNull()
    {
        $this->assertSame('', Str::ascii(null));
        $this->assertTrue(Str::isAscii(null));
        $this->assertSame('', Str::slug(null));
    }

    public function testPadBoth()
    {
        $this->assertSame('__Alien___', Str::padBoth('Alien', 10, '_'));
        $this->assertSame('  Alien   ', Str::padBoth('Alien', 10));
        $this->assertSame('  ❤MultiByte☆   ', Str::padBoth('❤MultiByte☆', 16));
        $this->assertSame('❤☆❤MultiByte☆❤☆❤', Str::padBoth('❤MultiByte☆', 16, '❤☆'));
    }

    public function testPadLeft()
    {
        $this->assertSame('-=-=-Alien', Str::padLeft('Alien', 10, '-='));
        $this->assertSame('     Alien', Str::padLeft('Alien', 10));
        $this->assertSame('     ❤MultiByte☆', Str::padLeft('❤MultiByte☆', 16));
        $this->assertSame('❤☆❤☆❤❤MultiByte☆', Str::padLeft('❤MultiByte☆', 16, '❤☆'));
    }

    public function testPadRight()
    {
        $this->assertSame('Alien-=-=-', Str::padRight('Alien', 10, '-='));
        $this->assertSame('Alien     ', Str::padRight('Alien', 10));
        $this->assertSame('❤MultiByte☆     ', Str::padRight('❤MultiByte☆', 16));
        $this->assertSame('❤MultiByte☆❤☆❤☆❤', Str::padRight('❤MultiByte☆', 16, '❤☆'));
    }

    public function testSwapKeywords(): void
    {
        $this->assertSame(
            'PHP 8 is fantastic',
            Str::swap([
                'PHP' => 'PHP 8',
                'awesome' => 'fantastic',
            ], 'PHP is awesome')
        );

        $this->assertSame(
            'foo bar baz',
            Str::swap([
                'ⓐⓑ' => 'baz',
            ], 'foo bar ⓐⓑ')
        );
    }

    public function testWordCount()
    {
        $this->assertEquals(2, Str::wordCount('Hello, world!'));
        $this->assertEquals(10, Str::wordCount('Hi, this is my first contribution to the Laravel framework.'));

        $this->assertEquals(0, Str::wordCount('мама'));
        $this->assertEquals(0, Str::wordCount('мама мыла раму'));

        $this->assertEquals(1, Str::wordCount('мама', 'абвгдеёжзийклмнопрстуфхцчшщъыьэюяАБВГДЕЁЖЗИЙКЛМНОПРСТУФХЦЧШЩЪЫЬЭЮЯ'));
        $this->assertEquals(3, Str::wordCount('мама мыла раму', 'абвгдеёжзийклмнопрстуфхцчшщъыьэюяАБВГДЕЁЖЗИЙКЛМНОПРСТУФХЦЧШЩЪЫЬЭЮЯ'));

        $this->assertEquals(1, Str::wordCount('МАМА', 'абвгдеёжзийклмнопрстуфхцчшщъыьэюяАБВГДЕЁЖЗИЙКЛМНОПРСТУФХЦЧШЩЪЫЬЭЮЯ'));
        $this->assertEquals(3, Str::wordCount('МАМА МЫЛА РАМУ', 'абвгдеёжзийклмнопрстуфхцчшщъыьэюяАБВГДЕЁЖЗИЙКЛМНОПРСТУФХЦЧШЩЪЫЬЭЮЯ'));
    }

    public function testWordWrap()
    {
        $this->assertEquals('Hello<br />World', Str::wordWrap('Hello World', 3, '<br />'));
        $this->assertEquals('Hel<br />lo<br />Wor<br />ld', Str::wordWrap('Hello World', 3, '<br />', true));

        $this->assertEquals('❤Multi<br />Byte☆❤☆❤☆❤', Str::wordWrap('❤Multi Byte☆❤☆❤☆❤', 3, '<br />'));
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

    public static function uuidVersionList()
    {
        return [
            ['00000000-0000-0000-0000-000000000000', null, true],
            ['00000000-0000-0000-0000-000000000000', 0, true],
            ['00000000-0000-0000-0000-000000000000', 1, false],
            ['00000000-0000-0000-0000-000000000000', 42, false],
            ['145a1e72-d11d-11e8-a8d5-f2801f1b9fd1', null, true],
            ['145a1e72-d11d-11e8-a8d5-f2801f1b9fd1', 1, true],
            ['145a1e72-d11d-11e8-a8d5-f2801f1b9fd1', 4, false],
            ['145a1e72-d11d-11e8-a8d5-f2801f1b9fd1', 42, false],
            ['ff6f8cb0-c57d-21e1-9b21-0800200c9a66', null, true],
            ['ff6f8cb0-c57d-21e1-9b21-0800200c9a66', 1, false],
            ['ff6f8cb0-c57d-21e1-9b21-0800200c9a66', 2, true],
            ['ff6f8cb0-c57d-21e1-9b21-0800200c9a66', 42, false],
            ['76a4ba72-cc4e-3e1d-b52d-856382f408c3', null, true],
            ['76a4ba72-cc4e-3e1d-b52d-856382f408c3', 1, false],
            ['76a4ba72-cc4e-3e1d-b52d-856382f408c3', 3, true],
            ['76a4ba72-cc4e-3e1d-b52d-856382f408c3', 42, false],
            ['a0a2a2d2-0b87-4a18-83f2-2529882be2de', null, true],
            ['a0a2a2d2-0b87-4a18-83f2-2529882be2de', 1, false],
            ['a0a2a2d2-0b87-4a18-83f2-2529882be2de', 4, true],
            ['a0a2a2d2-0b87-4a18-83f2-2529882be2de', 42, false],
            ['d3b2b5a9-d433-5c58-b038-4fa13696e357', null, true],
            ['d3b2b5a9-d433-5c58-b038-4fa13696e357', 1, false],
            ['d3b2b5a9-d433-5c58-b038-4fa13696e357', 5, true],
            ['d3b2b5a9-d433-5c58-b038-4fa13696e357', 42, false],
            ['1ef97d97-b5ab-67d8-9f12-5600051f1387', null, true],
            ['1ef97d97-b5ab-67d8-9f12-5600051f1387', 1, false],
            ['1ef97d97-b5ab-67d8-9f12-5600051f1387', 6, true],
            ['1ef97d97-b5ab-67d8-9f12-5600051f1387', 42, false],
            ['0192e4b9-92eb-7aec-8707-1becfb1e3eb7', null, true],
            ['0192e4b9-92eb-7aec-8707-1becfb1e3eb7', 1, false],
            ['0192e4b9-92eb-7aec-8707-1becfb1e3eb7', 7, true],
            ['0192e4b9-92eb-7aec-8707-1becfb1e3eb7', 42, false],
            ['07e80a1f-1629-831f-811f-c595103c91b5', null, true],
            ['07e80a1f-1629-831f-811f-c595103c91b5', 1, false],
            ['07e80a1f-1629-831f-811f-c595103c91b5', 8, true],
            ['07e80a1f-1629-831f-811f-c595103c91b5', 42, false],
            ['FFFFFFFF-FFFF-FFFF-FFFF-FFFFFFFFFFFF', null, true],
            ['FFFFFFFF-FFFF-FFFF-FFFF-FFFFFFFFFFFF', 1, false],
            ['FFFFFFFF-FFFF-FFFF-FFFF-FFFFFFFFFFFF', 42, false],
            ['FFFFFFFF-FFFF-FFFF-FFFF-FFFFFFFFFFFF', 'max', true],
            ['a0a2a2d2-0b87-4a18-83f2-2529882be2de', null, true],
            ['a0a2a2d2-0b87-4a18-83f2-2529882be2de', 1, false],
            ['a0a2a2d2-0b87-4a18-83f2-2529882be2de', 4, true],
            ['a0a2a2d2-0b87-4a18-83f2-2529882be2de', 42, false],
            ['zf6f8cb0-c57d-11e1-9b21-0800200c9a66', null, false],
            ['zf6f8cb0-c57d-11e1-9b21-0800200c9a66', 1, false],
            ['zf6f8cb0-c57d-11e1-9b21-0800200c9a66', 4, false],
            ['zf6f8cb0-c57d-11e1-9b21-0800200c9a66', 42, false],
        ];
    }

    public static function strContainsProvider()
    {
        return [
            ['Taylor', 'ylo', true, true],
            ['Taylor', 'ylo', true, false],
            ['Taylor', 'taylor', true, true],
            ['Taylor', 'taylor', false, false],
            ['Taylor', ['ylo'], true, true],
            ['Taylor', ['ylo'], true, false],
            ['Taylor', ['xxx', 'ylo'], true, true],
            ['Taylor', collect(['xxx', 'ylo']), true, true],
            ['Taylor', ['xxx', 'ylo'], true, false],
            ['Taylor', 'xxx', false],
            ['Taylor', ['xxx'], false],
            ['Taylor', '', false],
            ['', '', false],
        ];
    }

    public static function strContainsAllProvider()
    {
        return [
            ['Taylor Otwell', ['taylor', 'otwell'], false, false],
            ['Taylor Otwell', ['taylor', 'otwell'], true, true],
            ['Taylor Otwell', ['taylor'], false, false],
            ['Taylor Otwell', ['taylor'], true, true],
            ['Taylor Otwell', ['taylor', 'xxx'], false, false],
            ['Taylor Otwell', ['taylor', 'xxx'], false, true],
        ];
    }

    public static function strDoesntContainProvider()
    {
        return [
            ['Tar', 'ylo', true, true],
        ];
    }

    public function testMarkdown()
    {
        $this->assertSame("<p><em>hello world</em></p>\n", Str::markdown('*hello world*'));
        $this->assertSame("<h1>hello world</h1>\n", Str::markdown('# hello world'));
    }

    public function testInlineMarkdown()
    {
        $this->assertSame("<em>hello world</em>\n", Str::inlineMarkdown('*hello world*'));
        $this->assertSame("<a href=\"https://laravel.com\"><strong>Laravel</strong></a>\n", Str::inlineMarkdown('[**Laravel**](https://laravel.com)'));
    }

    public function testRepeat()
    {
        $this->assertSame('', Str::repeat('Hello', 0));
        $this->assertSame('Hello', Str::repeat('Hello', 1));
        $this->assertSame('aaaaa', Str::repeat('a', 5));
        $this->assertSame('', Str::repeat('', 5));
    }

    public function testRepeatWhenTimesIsNegative()
    {
        $this->expectException(ValueError::class);
        Str::repeat('Hello', -2);
    }

    #[DataProvider('specialCharacterProvider')]
    public function testTransliterate(string $value, string $expected): void
    {
        $this->assertSame($expected, Str::transliterate($value));
    }

    public static function specialCharacterProvider(): array
    {
        return [
            ['ⓐⓑⓒⓓⓔⓕⓖⓗⓘⓙⓚⓛⓜⓝⓞⓟⓠⓡⓢⓣⓤⓥⓦⓧⓨⓩ', 'abcdefghijklmnopqrstuvwxyz'],
            ['⓪①②③④⑤⑥⑦⑧⑨⑩⑪⑫⑬⑭⑮⑯⑰⑱⑲⑳', '01234567891011121314151617181920'],
            ['⓵⓶⓷⓸⓹⓺⓻⓼⓽⓾', '12345678910'],
            ['⓿⓫⓬⓭⓮⓯⓰⓱⓲⓳⓴', '011121314151617181920'],
            ['ⓣⓔⓢⓣ@ⓛⓐⓡⓐⓥⓔⓛ.ⓒⓞⓜ', 'test@laravel.com'],
            ['🎂', '?'],
            ['abcdefghijklmnopqrstuvwxyz', 'abcdefghijklmnopqrstuvwxyz'],
            ['0123456789', '0123456789'],
        ];
    }

    public function testTransliterateOverrideUnknown(): void
    {
        $this->assertSame('HHH', Str::transliterate('🎂🚧🏆', 'H'));
        $this->assertSame('Hello', Str::transliterate('🎂', 'Hello'));
    }

    #[DataProvider('specialCharacterProvider')]
    public function testTransliterateStrict(string $value, string $expected): void
    {
        $this->assertSame($expected, Str::transliterate($value, '?', true));
    }

    public function testItCanFreezeUuids()
    {
        $this->assertNotSame((string) Str::uuid(), (string) Str::uuid());
        $this->assertNotSame(Str::uuid(), Str::uuid());

        $uuid = Str::freezeUuids();

        $this->assertSame($uuid, Str::uuid());
        $this->assertSame(Str::uuid(), Str::uuid());
        $this->assertSame((string) $uuid, (string) Str::uuid());
        $this->assertSame((string) Str::uuid(), (string) Str::uuid());

        Str::createUuidsNormally();

        $this->assertNotSame(Str::uuid(), Str::uuid());
        $this->assertNotSame((string) Str::uuid(), (string) Str::uuid());
    }

    public function testItCanFreezeUuidsInAClosure()
    {
        $uuids = [];

        $uuid = Str::freezeUuids(function ($uuid) use (&$uuids) {
            $uuids[] = $uuid;
            $uuids[] = Str::uuid();
            $uuids[] = Str::uuid();
        });

        $this->assertSame($uuid, $uuids[0]);
        $this->assertSame((string) $uuid, (string) $uuids[0]);
        $this->assertSame((string) $uuids[0], (string) $uuids[1]);
        $this->assertSame($uuids[0], $uuids[1]);
        $this->assertSame((string) $uuids[0], (string) $uuids[1]);
        $this->assertSame($uuids[1], $uuids[2]);
        $this->assertSame((string) $uuids[1], (string) $uuids[2]);
        $this->assertNotSame(Str::uuid(), Str::uuid());
        $this->assertNotSame((string) Str::uuid(), (string) Str::uuid());

        Str::createUuidsNormally();
    }

    public function testItCreatesUuidsNormallyAfterFailureWithinFreezeMethod()
    {
        try {
            Str::freezeUuids(function () {
                Str::createUuidsUsing(fn () => Str::of('1234'));
                $this->assertSame('1234', Str::uuid()->toString());
                throw new \Exception('Something failed.');
            });
        } catch (\Exception) {
            $this->assertNotSame('1234', Str::uuid()->toString());
        }
    }

    public function testItCanSpecifyASequenceOfUuidsToUtilise()
    {
        Str::createUuidsUsingSequence([
            0 => ($zeroth = Str::uuid()),
            1 => ($first = Str::uuid7()),
            // just generate a random one here...
            3 => ($third = Str::uuid()),
            // continue to generate random uuids...
        ]);

        $retrieved = Str::uuid();
        $this->assertSame($zeroth, $retrieved);
        $this->assertSame((string) $zeroth, (string) $retrieved);

        $retrieved = Str::uuid();
        $this->assertSame($first, $retrieved);
        $this->assertSame((string) $first, (string) $retrieved);

        $retrieved = Str::uuid();
        $this->assertFalse(in_array($retrieved, [$zeroth, $first, $third], true));
        $this->assertFalse(in_array((string) $retrieved, [(string) $zeroth, (string) $first, (string) $third], true));

        $retrieved = Str::uuid();
        $this->assertSame($third, $retrieved);
        $this->assertSame((string) $third, (string) $retrieved);

        $retrieved = Str::uuid();
        $this->assertFalse(in_array($retrieved, [$zeroth, $first, $third], true));
        $this->assertFalse(in_array((string) $retrieved, [(string) $zeroth, (string) $first, (string) $third], true));

        Str::createUuidsNormally();
    }

    public function testItCanSpecifyAFallbackForASequence()
    {
        Str::createUuidsUsingSequence([Str::uuid(), Str::uuid()], fn () => throw new Exception('Out of Uuids.'));
        Str::uuid();
        Str::uuid();

        try {
            $this->expectExceptionMessage('Out of Uuids.');
            Str::uuid();
            $this->fail();
        } finally {
            Str::createUuidsNormally();
        }
    }

    public function testItCanFreezeUlids()
    {
        $this->assertNotSame((string) Str::ulid(), (string) Str::ulid());
        $this->assertNotSame(Str::ulid(), Str::ulid());

        $ulid = Str::freezeUlids();

        $this->assertSame($ulid, Str::ulid());
        $this->assertSame(Str::ulid(), Str::ulid());
        $this->assertSame((string) $ulid, (string) Str::ulid());
        $this->assertSame((string) Str::ulid(), (string) Str::ulid());

        Str::createUlidsNormally();

        $this->assertNotSame(Str::ulid(), Str::ulid());
        $this->assertNotSame((string) Str::ulid(), (string) Str::ulid());
    }

    public function testItCanFreezeUlidsInAClosure()
    {
        $ulids = [];

        $ulid = Str::freezeUlids(function ($ulid) use (&$ulids) {
            $ulids[] = $ulid;
            $ulids[] = Str::ulid();
            $ulids[] = Str::ulid();
        });

        $this->assertSame($ulid, $ulids[0]);
        $this->assertSame((string) $ulid, (string) $ulids[0]);
        $this->assertSame((string) $ulids[0], (string) $ulids[1]);
        $this->assertSame($ulids[0], $ulids[1]);
        $this->assertSame((string) $ulids[0], (string) $ulids[1]);
        $this->assertSame($ulids[1], $ulids[2]);
        $this->assertSame((string) $ulids[1], (string) $ulids[2]);
        $this->assertNotSame(Str::ulid(), Str::ulid());
        $this->assertNotSame((string) Str::ulid(), (string) Str::ulid());

        Str::createUlidsNormally();
    }

    public function testItCreatesUlidsNormallyAfterFailureWithinFreezeMethod()
    {
        try {
            Str::freezeUlids(function () {
                Str::createUlidsUsing(fn () => Str::of('1234'));
                $this->assertSame('1234', (string) Str::ulid());
                throw new \Exception('Something failed');
            });
        } catch (\Exception) {
            $this->assertNotSame('1234', (string) Str::ulid());
        }
    }

    public function testItCanSpecifyASequenceOfUlidsToUtilise()
    {
        Str::createUlidsUsingSequence([
            0 => ($zeroth = Str::ulid()),
            1 => ($first = Str::ulid()),
            // just generate a random one here...
            3 => ($third = Str::ulid()),
            // continue to generate random ulids...
        ]);

        $retrieved = Str::ulid();
        $this->assertSame($zeroth, $retrieved);
        $this->assertSame((string) $zeroth, (string) $retrieved);

        $retrieved = Str::ulid();
        $this->assertSame($first, $retrieved);
        $this->assertSame((string) $first, (string) $retrieved);

        $retrieved = Str::ulid();
        $this->assertFalse(in_array($retrieved, [$zeroth, $first, $third], true));
        $this->assertFalse(in_array((string) $retrieved, [(string) $zeroth, (string) $first, (string) $third], true));

        $retrieved = Str::ulid();
        $this->assertSame($third, $retrieved);
        $this->assertSame((string) $third, (string) $retrieved);

        $retrieved = Str::ulid();
        $this->assertFalse(in_array($retrieved, [$zeroth, $first, $third], true));
        $this->assertFalse(in_array((string) $retrieved, [(string) $zeroth, (string) $first, (string) $third], true));

        Str::createUlidsNormally();
    }

    public function testItCanSpecifyAFallbackForAUlidSequence()
    {
        Str::createUlidsUsingSequence(
            [Str::ulid(), Str::ulid()],
            fn () => throw new Exception('Out of Ulids'),
        );
        Str::ulid();
        Str::ulid();

        try {
            $this->expectExceptionMessage('Out of Ulids');
            Str::ulid();
            $this->fail();
        } finally {
            Str::createUlidsNormally();
        }
    }

    public function testPasswordCreation()
    {
        $this->assertTrue(strlen(Str::password()) === 32);

        $this->assertStringNotContainsString(' ', Str::password());
        $this->assertStringContainsString(' ', Str::password(spaces: true));

        $this->assertTrue(
            Str::of(Str::password())->contains(['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'])
        );
    }

    public function testToBase64()
    {
        $this->assertSame(base64_encode('foo'), Str::toBase64('foo'));
        $this->assertSame(base64_encode('foobar'), Str::toBase64('foobar'));
    }

    public function testFromBase64()
    {
        $this->assertSame('foo', Str::fromBase64(base64_encode('foo')));
        $this->assertSame('foobar', Str::fromBase64(base64_encode('foobar'), true));
    }

    public function testChopStart()
    {
        foreach ([
            ['http://laravel.com', 'http://', 'laravel.com'],
            ['http://-http://', 'http://', '-http://'],
            ['http://laravel.com', 'htp:/', 'http://laravel.com'],
            ['http://laravel.com', 'http://www.', 'http://laravel.com'],
            ['http://laravel.com', '-http://', 'http://laravel.com'],
            ['http://laravel.com', ['https://', 'http://'], 'laravel.com'],
            ['http://www.laravel.com', ['http://', 'www.'], 'www.laravel.com'],
            ['http://http-is-fun.test', 'http://', 'http-is-fun.test'],
            ['🌊✋', '🌊', '✋'],
            ['🌊✋', '✋', '🌊✋'],
        ] as $value) {
            [$subject, $needle, $expected] = $value;

            $this->assertSame($expected, Str::chopStart($subject, $needle));
        }
    }

    public function testChopEnd()
    {
        foreach ([
            ['path/to/file.php', '.php', 'path/to/file'],
            ['.php-.php', '.php', '.php-'],
            ['path/to/file.php', '.ph', 'path/to/file.php'],
            ['path/to/file.php', 'foo.php', 'path/to/file.php'],
            ['path/to/file.php', '.php-', 'path/to/file.php'],
            ['path/to/file.php', ['.html', '.php'], 'path/to/file'],
            ['path/to/file.php', ['.php', 'file'], 'path/to/file'],
            ['path/to/php.php', '.php', 'path/to/php'],
            ['✋🌊', '🌊', '✋'],
            ['✋🌊', '✋', '✋🌊'],
        ] as $value) {
            [$subject, $needle, $expected] = $value;

            $this->assertSame($expected, Str::chopEnd($subject, $needle));
        }
    }

    public function testReplaceMatches()
    {
        // Test basic string replacement
        $this->assertSame('foo bar bar', Str::replaceMatches('/baz/', 'bar', 'foo baz bar'));
        $this->assertSame('foo baz baz', Str::replaceMatches('/404/', 'found', 'foo baz baz'));

        // Test with array of patterns
        $this->assertSame('foo XXX YYY', Str::replaceMatches(['/bar/', '/baz/'], ['XXX', 'YYY'], 'foo bar baz'));

        // Test with callback
        $result = Str::replaceMatches('/ba(.)/', function ($match) {
            return 'ba'.strtoupper($match[1]);
        }, 'foo baz bar');

        $this->assertSame('foo baZ baR', $result);

        $result = Str::replaceMatches('/(\d+)/', function ($match) {
            return $match[1] * 2;
        }, 'foo 123 bar 456');

        $this->assertSame('foo 246 bar 912', $result);

        // Test with limit parameter
        $this->assertSame('foo baz baz', Str::replaceMatches('/ba(.)/', 'ba$1', 'foo baz baz', 1));

        $result = Str::replaceMatches('/ba(.)/', function ($match) {
            return 'ba'.strtoupper($match[1]);
        }, 'foo baz baz bar', 1);

        $this->assertSame('foo baZ baz bar', $result);
    }

    public function testPlural(): void
    {
        $this->assertSame('Laracon', Str::plural('Laracon', 1));
        $this->assertSame('Laracons', Str::plural('Laracon', 3));
        $this->assertSame('1,000 Laracons', Str::plural('Laracon', 1000, prependCount: true));
    }

    public function testPluralPascal(): void
    {
        // Test basic functionality with default count
        $this->assertSame('UserGroups', Str::pluralPascal('UserGroup'));
        $this->assertSame('ProductCategories', Str::pluralPascal('ProductCategory'));

        // Test with different count values and array
        $this->assertSame('UserGroups', Str::pluralPascal('UserGroup', 0)); // plural
        $this->assertSame('UserGroup', Str::pluralPascal('UserGroup', 1));  // singular
        $this->assertSame('UserGroups', Str::pluralPascal('UserGroup', 2)); // plural
        $this->assertSame('UserGroups', Str::pluralPascal('UserGroup', []));   // plural (empty array count is 0)

        // Test with Countable
        $countable = new class implements \Countable
        {
            public function count(): int
            {
                return 3;
            }
        };

        $this->assertSame('UserGroups', Str::pluralPascal('UserGroup', $countable));
    }
}

class StringableObjectStub
{
    private $value;

    public function __construct($value)
    {
        $this->value = $value;
    }

    public function __toString()
    {
        return $this->value;
    }
}
