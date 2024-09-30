<?php

namespace Illuminate\Tests\Support;

use Illuminate\Support\PatternMatcher;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ValueError;

class SupportStrTest extends TestCase
{
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
        // Test for multibyte string support
        $this->assertTrue(Str::startsWith('Jönköping', 'Jö'));
        $this->assertTrue(Str::startsWith('Malmö', 'Malmö'));
        $this->assertFalse(Str::startsWith('Jönköping', 'Jonko'));
        $this->assertFalse(Str::startsWith('Malmö', 'Malmo'));
        $this->assertTrue(Str::startsWith('你好', '你'));
        $this->assertFalse(Str::startsWith('你好', '好'));
        $this->assertFalse(Str::startsWith('你好', 'a'));
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
        // Test for multibyte string support
        $this->assertTrue(Str::endsWith('Jönköping', 'öping'));
        $this->assertTrue(Str::endsWith('Malmö', 'mö'));
        $this->assertFalse(Str::endsWith('Jönköping', 'oping'));
        $this->assertFalse(Str::endsWith('Malmö', 'mo'));
        $this->assertTrue(Str::endsWith('你好', '好'));
        $this->assertFalse(Str::endsWith('你好', '你'));
        $this->assertFalse(Str::endsWith('你好', 'a'));
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

    public function testDedup()
    {
        $this->assertSame(' laravel php framework ', Str::deduplicate(' laravel   php  framework '));
        $this->assertSame('what', Str::deduplicate('whaaat', 'a'));
        $this->assertSame('/some/odd/path/', Str::deduplicate('/some//odd//path/', '/'));
        $this->assertSame('ムだム', Str::deduplicate('ムだだム', 'だ'));
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

    public function testReverse()
    {
        $this->assertSame('FooBar', Str::reverse('raBooF'));
        $this->assertSame('Teniszütő', Str::reverse('őtüzsineT'));
        $this->assertSame('❤MultiByte☆', Str::reverse('☆etyBitluM❤'));
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

    public function testAsciiNull()
    {
        $this->assertSame('', Str::ascii(null));
        $this->assertTrue(PatternMatcher::isAscii(null));
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
            'http://laravel.com' => ['http://', 'laravel.com'],
            'http://-http://' => ['http://', '-http://'],
            'http://laravel.com' => ['htp:/', 'http://laravel.com'],
            'http://laravel.com' => ['http://www.', 'http://laravel.com'],
            'http://laravel.com' => ['-http://', 'http://laravel.com'],
            'http://laravel.com' => [['https://', 'http://'], 'laravel.com'],
            'http://www.laravel.com' => [['http://', 'www.'], 'www.laravel.com'],
            'http://http-is-fun.test' => ['http://', 'http-is-fun.test'],
            '🌊✋' => ['🌊', '✋'],
            '🌊✋' => ['✋', '🌊✋'],
        ] as $subject => $value) {
            [$needle, $expected] = $value;

            $this->assertSame($expected, Str::chopStart($subject, $needle));
        }
    }

    public function testChopEnd()
    {
        foreach ([
            'path/to/file.php' => ['.php', 'path/to/file'],
            '.php-.php' => ['.php', '.php-'],
            'path/to/file.php' => ['.ph', 'path/to/file.php'],
            'path/to/file.php' => ['foo.php', 'path/to/file.php'],
            'path/to/file.php' => ['.php-', 'path/to/file.php'],
            'path/to/file.php' => [['.html', '.php'], 'path/to/file'],
            'path/to/file.php' => [['.php', 'file'], 'path/to/file'],
            'path/to/php.php' => ['.php', 'path/to/php'],
            '✋🌊' => ['🌊', '✋'],
            '✋🌊' => ['✋', '✋🌊'],
        ] as $subject => $value) {
            [$needle, $expected] = $value;

            $this->assertSame($expected, Str::chopEnd($subject, $needle));
        }
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
