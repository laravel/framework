<?php

namespace Illuminate\Tests\Foundation\Http;

use Illuminate\Foundation\Http\HtmlDumper;
use PHPUnit\Framework\TestCase;
use stdClass;
use Symfony\Component\VarDumper\Caster\ReflectionCaster;
use Symfony\Component\VarDumper\Cloner\VarCloner;

class HtmlDumperTest extends TestCase
{
    protected function setUp(): void
    {
        HtmlDumper::resolveDumpSourceUsing(function () {
            return [
                '/my-work-director/app/routes/console.php',
                'app/routes/console.php',
                18,
            ];
        });
    }

    public function testString()
    {
        $output = $this->dump('string');

        $expected = "string</span>\"<span style=\"color: #A0A0A0; font-family: Menlo\"> // app/routes/console.php:18</span>\n</pre>";

        $this->assertStringContainsString($expected, $output);
    }

    public function testInteger()
    {
        $output = $this->dump(1);

        $expected = "1</span><span style=\"color: #A0A0A0; font-family: Menlo\"> // app/routes/console.php:18</span>\n</pre>";

        $this->assertStringContainsString($expected, $output);
    }

    public function testFloat()
    {
        $output = $this->dump(1.1);

        $expected = "1.1</span><span style=\"color: #A0A0A0; font-family: Menlo\"> // app/routes/console.php:18</span>\n</pre>";

        $this->assertStringContainsString($expected, $output);
    }

    public function testArray()
    {
        $output = $this->dump(['string', 1, 1.1, ['string', 1, 1.1]]);

        $expected = '<samp data-depth=1 class=sf-dump-expanded><span style="color: #A0A0A0; font-family: Menlo"> // app/routes/console.php:18</span>';

        $this->assertStringContainsString($expected, $output);
    }

    public function testBoolean()
    {
        $output = $this->dump(true);

        $expected = "true</span><span style=\"color: #A0A0A0; font-family: Menlo\"> // app/routes/console.php:18</span>\n</pre>";

        $this->assertStringContainsString($expected, $output);
    }

    public function testObject()
    {
        $user = new stdClass();
        $user->name = 'Guus';

        $output = $this->dump($user);

        $expected = '<samp data-depth=1 class=sf-dump-expanded><span style="color: #A0A0A0; font-family: Menlo"> // app/routes/console.php:18</span>';

        $this->assertStringContainsString($expected, $output);
    }

    public function testNull()
    {
        $output = $this->dump(null);

        $expected = "null</span><span style=\"color: #A0A0A0; font-family: Menlo\"> // app/routes/console.php:18</span>\n</pre>";

        $this->assertStringContainsString($expected, $output);
    }

    public function testUnresolvableSource()
    {
        HtmlDumper::resolveDumpSourceUsing(fn () => null);

        $output = $this->dump('string');

        $expected = "string</span>\"\n</pre>";

        $this->assertStringContainsString($expected, $output);
    }

    protected function dump($value)
    {
        $outputFile = stream_get_meta_data(tmpfile())['uri'];

        $dumper = new HtmlDumper('/my-work-directory');
        $dumper->setOutput($outputFile);

        $cloner = tap(new VarCloner())->addCasters(ReflectionCaster::UNSET_CLOSURE_FILE_INFO);

        $dumper->dumpWithSource($cloner->cloneVar($value));

        return tap(file_get_contents($outputFile), fn () => @unlink($outputFile));
    }

    protected function tearDown(): void
    {
        HtmlDumper::resolveDumpSourceUsing(null);
    }
}
