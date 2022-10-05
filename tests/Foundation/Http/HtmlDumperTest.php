<?php

namespace Illuminate\Tests\Foundation\Http;

use Illuminate\Config\Repository;
use Illuminate\Container\Container;
use Illuminate\Foundation\Http\HtmlDumper;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use stdClass;
use Symfony\Component\VarDumper\Caster\ReflectionCaster;
use Symfony\Component\VarDumper\Cloner\VarCloner;

class HtmlDumperTest extends TestCase
{
    protected $app;

    protected function setUp(): void
    {
        HtmlDumper::resolveDumpSourceUsing(function () {
            return [
                '/my-work-director/app/routes/console.php',
                'app/routes/console.php',
                18,
            ];
        });

        $this->app = Container::getInstance();
    }

    public function testString()
    {
        $output = $this->dump('string');

        $expected = "string</span>\"<span style=\"color: #A0A0A0;\"> // app/routes/console.php:18</span>\n</pre>";

        $this->assertStringContainsString($expected, $output);
    }

    public function testInteger()
    {
        $output = $this->dump(1);

        $expected = "1</span><span style=\"color: #A0A0A0;\"> // app/routes/console.php:18</span>\n</pre>";

        $this->assertStringContainsString($expected, $output);
    }

    public function testFloat()
    {
        $output = $this->dump(1.1);

        $expected = "1.1</span><span style=\"color: #A0A0A0;\"> // app/routes/console.php:18</span>\n</pre>";

        $this->assertStringContainsString($expected, $output);
    }

    public function testArray()
    {
        $output = $this->dump(['string', 1, 1.1, ['string', 1, 1.1]]);

        $expected = '<samp data-depth=1 class=sf-dump-expanded><span style="color: #A0A0A0;"> // app/routes/console.php:18</span>';

        $this->assertStringContainsString($expected, $output);
    }

    public function testBoolean()
    {
        $output = $this->dump(true);

        $expected = "true</span><span style=\"color: #A0A0A0;\"> // app/routes/console.php:18</span>\n</pre>";

        $this->assertStringContainsString($expected, $output);
    }

    public function testObject()
    {
        $user = new stdClass();
        $user->name = 'Guus';

        $output = $this->dump($user);

        $expected = '<samp data-depth=1 class=sf-dump-expanded><span style="color: #A0A0A0;"> // app/routes/console.php:18</span>';

        $this->assertStringContainsString($expected, $output);
    }

    public function testNull()
    {
        $output = $this->dump(null);

        $expected = "null</span><span style=\"color: #A0A0A0;\"> // app/routes/console.php:18</span>\n</pre>";

        $this->assertStringContainsString($expected, $output);
    }

    public function testUnresolvableSource()
    {
        HtmlDumper::resolveDumpSourceUsing(fn () => null);

        $output = $this->dump('string');

        $expected = "string</span>\"\n</pre>";

        $this->assertStringContainsString($expected, $output);
    }

    public function testWhenIsFileViewIsNotViewCompiled()
    {
        $file = '/my-work-directory/routes/web.php';

        $dumper = new HtmlDumper(
            '/my-work-directory',
            '/my-work-directory/storage/framework/views'
        );

        $reflection = new ReflectionClass($dumper);
        $method = $reflection->getMethod('isCompiledViewFile');
        $method->setAccessible(true);
        $isCompiledViewFile = $method->invoke($dumper, $file);

        $this->assertFalse($isCompiledViewFile);
    }

    public function testWhenIsFileViewIsViewCompiled()
    {
        $file = '/my-work-directory/storage/framework/views/6687c33c38b71a8560.php';

        $dumper = new HtmlDumper(
            '/my-work-directory',
            '/my-work-directory/storage/framework/views'
        );

        $reflection = new ReflectionClass($dumper);
        $method = $reflection->getMethod('isCompiledViewFile');
        $method->setAccessible(true);
        $isCompiledViewFile = $method->invoke($dumper, $file);

        $this->assertTrue($isCompiledViewFile);
    }

    public function testGetOriginalViewCompiledFile()
    {
        $compiled = __DIR__.'/../fixtures/fake-compiled-view.php';
        $original = '/my-work-directory/resources/views/welcome.blade.php';

        $dumper = new HtmlDumper(
            '/my-work-directory',
            '/my-work-directory/storage/framework/views'
        );

        $reflection = new ReflectionClass($dumper);
        $method = $reflection->getMethod('getOriginalFileForCompiledView');
        $method->setAccessible(true);

        $this->assertSame($original, $method->invoke($dumper, $compiled));
    }

    public function testWhenGetOriginalViewCompiledFileFails()
    {
        $compiled = __DIR__.'/../fixtures/fake-compiled-view-without-source-map.php';
        $original = $compiled;

        $dumper = new HtmlDumper(
            '/my-work-directory',
            '/my-work-directory/storage/framework/views'
        );

        $reflection = new ReflectionClass($dumper);
        $method = $reflection->getMethod('getOriginalFileForCompiledView');
        $method->setAccessible(true);

        $this->assertSame($original, $method->invoke($dumper, $compiled));
    }

    public function testUnresolvableLine()
    {
        HtmlDumper::resolveDumpSourceUsing(function () {
            return [
                '/my-work-directory/resources/views/welcome.blade.php',
                'resources/views/welcome.blade.php',
                null,
            ];
        });

        $output = $this->dump('hey from view');

        $expected = "hey from view</span>\"<span style=\"color: #A0A0A0;\"> // resources/views/welcome.blade.php</span>\n</pre>";

        $this->assertStringContainsString($expected, $output);
    }

    public function testHref()
    {
        $dumper = new HtmlDumper(
            '/my-work-directory',
            '/my-work-directory/storage/framework/views'
        );

        // Failure...
        $href = (fn () => $this->resolveSourceHref(
            '/my-work-directory/app/my-file',
            10,
        ))->call($dumper);
        $this->assertNull($href);

        $config = new Repository();
        $this->app->instance('config', $config);
        $resolveSourceHref = fn () => (fn () => $this->resolveSourceHref(
            '/my-work-directory/app/my-file',
            10,
        ))->call($dumper);

        // Empty...
        $this->assertNull($resolveSourceHref());

        // When editor name is provided...
        $config->set('app.editor', 'phpstorm');
        $this->assertSame(
            'phpstorm://open?file=/my-work-directory/app/my-file&line=10', $resolveSourceHref()
        );

        // When editor name is provided on array format...
        $config->set('app.editor', ['name' => 'phpstorm']);
        $this->assertSame(
            'phpstorm://open?file=/my-work-directory/app/my-file&line=10', $resolveSourceHref()
        );

        // When editor name and base path is provided on array format...
        $config->set('app.editor', ['name' => 'phpstorm', 'base_path' => '/my-docker-work-directory']);
        $this->assertSame(
            'phpstorm://open?file=/my-docker-work-directory/app/my-file&line=10', $resolveSourceHref());

        // When href is provided on array format...
        $config->set('app.editor', ['href' => 'vscode://open?file={file}&line={line}']);
        $this->assertSame(
            'vscode://open?file=/my-work-directory/app/my-file&line=10', $resolveSourceHref()
        );

        // When href and base path is provided on array format...
        $config->set('app.editor', ['href' => 'vscode://open?file={file}&line={line}', 'base_path' => '/my-docker-work-directory']);
        $this->assertSame(
            'vscode://open?file=/my-docker-work-directory/app/my-file&line=10', $resolveSourceHref()
        );

        // When editor name is provided...
        $config->set('app.editor', 'sublime');
        $this->assertSame('subl://open?url=file:///my-work-directory/app/my-file&line=10', $resolveSourceHref());

        // Missing line
        $config->set('app.editor', ['name' => 'vscode', 'base_path' => '/my-docker-work-directory']);

        $href = (fn () => $this->resolveSourceHref(
            '/my-work-directory/app/my-file',
            null,
        ))->call($dumper);
        $this->assertSame(
            'vscode://file//my-docker-work-directory/app/my-file:1',
            $href,
        );
    }

    protected function dump($value)
    {
        $outputFile = stream_get_meta_data(tmpfile())['uri'];

        $dumper = new HtmlDumper(
            '/my-work-directory',
            '/my-work-directory/storage/framework/views'
        );

        $dumper->setOutput($outputFile);

        $cloner = tap(new VarCloner())->addCasters(ReflectionCaster::UNSET_CLOSURE_FILE_INFO);

        $dumper->dumpWithSource($cloner->cloneVar($value));

        return tap(file_get_contents($outputFile), fn () => @unlink($outputFile));
    }

    protected function tearDown(): void
    {
        HtmlDumper::resolveDumpSourceUsing(null);
        Container::setInstance(null);
    }
}
