<?php

namespace Illuminate\Tests\View;

use Illuminate\Filesystem\Filesystem;
use Illuminate\View\Compilers\BladeCompiler;
use InvalidArgumentException;
use Mockery as m;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ViewBladeCompilerTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
    }

    public function testIsExpiredReturnsTrueIfCompiledFileDoesntExist()
    {
        $compiler = new BladeCompiler($files = $this->getFiles(), __DIR__);
        $files->shouldReceive('exists')->once()->with(__DIR__.'/'.hash('xxh128', 'v2foo').'.php')->andReturn(false);
        $this->assertTrue($compiler->isExpired('foo'));
    }

    public function testCannotConstructWithBadCachePath()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Please provide a valid cache path.');

        new BladeCompiler($this->getFiles(), null);
    }

    public function testIsExpiredReturnsTrueWhenModificationTimesWarrant()
    {
        $compiler = new BladeCompiler($files = $this->getFiles(), __DIR__);
        $files->shouldReceive('exists')->once()->with(__DIR__.'/'.hash('xxh128', 'v2foo').'.php')->andReturn(true);
        $files->shouldReceive('lastModified')->once()->with('foo')->andReturn(100);
        $files->shouldReceive('lastModified')->once()->with(__DIR__.'/'.hash('xxh128', 'v2foo').'.php')->andReturn(0);
        $this->assertTrue($compiler->isExpired('foo'));
    }

    public function testIsExpiredReturnsFalseWhenUseCacheIsTrueAndNoFileModification()
    {
        $compiler = new BladeCompiler($files = $this->getFiles(), __DIR__);
        $files->shouldReceive('exists')->once()->with(__DIR__.'/'.hash('xxh128', 'v2foo').'.php')->andReturn(true);
        $files->shouldReceive('lastModified')->once()->with('foo')->andReturn(0);
        $files->shouldReceive('lastModified')->once()->with(__DIR__.'/'.hash('xxh128', 'v2foo').'.php')->andReturn(100);
        $this->assertFalse($compiler->isExpired('foo'));
    }

    public function testIsExpiredReturnsTrueWhenUseCacheIsFalse()
    {
        $compiler = new BladeCompiler($files = $this->getFiles(), __DIR__, shouldCache: false);
        $this->assertTrue($compiler->isExpired('foo'));
    }

    public function testIsExpiredReturnsFalseWhenIgnoreCacheTimestampsIsTrue()
    {
        $compiler = new BladeCompiler($files = $this->getFiles(), __DIR__, shouldCheckTimestamps: false);
        $files->shouldReceive('exists')->once()->with(__DIR__.'/'.hash('xxh128', 'v2foo').'.php')->andReturn(true);
        $this->assertFalse($compiler->isExpired('foo'));
    }

    public function testCompilePathIsProperlyCreated()
    {
        $compiler = new BladeCompiler($this->getFiles(), __DIR__);
        $this->assertEquals(__DIR__.'/'.hash('xxh128', 'v2foo').'.php', $compiler->getCompiledPath('foo'));
    }

    public function testCompileCompilesFileAndReturnsContents()
    {
        $compiler = new BladeCompiler($files = $this->getFiles(), __DIR__);
        $files->shouldReceive('get')->once()->with('foo')->andReturn('Hello World');
        $files->shouldReceive('exists')->once()->with(__DIR__)->andReturn(true);
        $files->shouldReceive('exists')->once()->with(__DIR__.'/'.hash('xxh128', 'v2foo').'.php')->andReturn(false);
        $files->shouldReceive('put')->once()->with(__DIR__.'/'.hash('xxh128', 'v2foo').'.php', 'Hello World<?php /**PATH foo ENDPATH**/ ?>');
        $compiler->compile('foo');
    }

    public function testCompileCompilesFileAndReturnsContentsCreatingDirectory()
    {
        $compiler = new BladeCompiler($files = $this->getFiles(), __DIR__);
        $files->shouldReceive('get')->once()->with('foo')->andReturn('Hello World');
        $files->shouldReceive('exists')->once()->with(__DIR__)->andReturn(true);
        $files->shouldReceive('exists')->once()->with(__DIR__.'/'.hash('xxh128', 'v2foo').'.php')->andReturn(false);
        $files->shouldReceive('put')->once()->with(__DIR__.'/'.hash('xxh128', 'v2foo').'.php', 'Hello World<?php /**PATH foo ENDPATH**/ ?>');
        $compiler->compile('foo');
    }

    public function testCompileUpdatesCacheIfChanged()
    {
        $compiledPath = __DIR__.'/'.hash('xxh128', 'v2foo').'.php';
        $compiler = new BladeCompiler($files = $this->getFiles(), __DIR__);
        $files->shouldReceive('get')->once()->with('foo')->andReturn('Hello World');
        $files->shouldReceive('exists')->once()->with(__DIR__)->andReturn(true);
        $files->shouldReceive('exists')->once()->with($compiledPath)->andReturn(true);
        $files->shouldReceive('hash')->once()->with($compiledPath, 'xxh128')->andReturn(hash('xxh128', 'outdated content'));
        $files->shouldReceive('put')->once()->with($compiledPath, 'Hello World<?php /**PATH foo ENDPATH**/ ?>');
        $compiler->compile('foo');
    }

    public function testCompileKeepsCacheIfUnchanged()
    {
        $compiledPath = __DIR__.'/'.hash('xxh128', 'v2foo').'.php';
        $compiler = new BladeCompiler($files = $this->getFiles(), __DIR__);
        $files->shouldReceive('get')->once()->with('foo')->andReturn('Hello World');
        $files->shouldReceive('exists')->once()->with(__DIR__)->andReturn(false);
        $files->shouldReceive('makeDirectory')->once()->with(__DIR__, 0777, true, true);
        $files->shouldReceive('exists')->once()->with($compiledPath)->andReturn(true);
        $files->shouldReceive('hash')->once()->with($compiledPath, 'xxh128')->andReturn(hash('xxh128', 'Hello World<?php /**PATH foo ENDPATH**/ ?>'));
        $compiler->compile('foo');
    }

    public function testCompileCompilesAndGetThePath()
    {
        $compiler = new BladeCompiler($files = $this->getFiles(), __DIR__);
        $files->shouldReceive('get')->once()->with('foo')->andReturn('Hello World');
        $files->shouldReceive('exists')->once()->with(__DIR__)->andReturn(true);
        $files->shouldReceive('exists')->once()->with(__DIR__.'/'.hash('xxh128', 'v2foo').'.php')->andReturn(false);
        $files->shouldReceive('put')->once()->with(__DIR__.'/'.hash('xxh128', 'v2foo').'.php', 'Hello World<?php /**PATH foo ENDPATH**/ ?>');
        $compiler->compile('foo');
        $this->assertSame('foo', $compiler->getPath());
    }

    public function testCompileSetAndGetThePath()
    {
        $compiler = new BladeCompiler($files = $this->getFiles(), __DIR__);
        $compiler->setPath('foo');
        $this->assertSame('foo', $compiler->getPath());
    }

    public function testCompileWithPathSetBefore()
    {
        $compiler = new BladeCompiler($files = $this->getFiles(), __DIR__);
        $files->shouldReceive('get')->once()->with('foo')->andReturn('Hello World');
        $files->shouldReceive('exists')->once()->with(__DIR__)->andReturn(true);
        $files->shouldReceive('exists')->once()->with(__DIR__.'/'.hash('xxh128', 'v2foo').'.php')->andReturn(false);
        $files->shouldReceive('put')->once()->with(__DIR__.'/'.hash('xxh128', 'v2foo').'.php', 'Hello World<?php /**PATH foo ENDPATH**/ ?>');
        // set path before compilation
        $compiler->setPath('foo');
        // trigger compilation with $path
        $compiler->compile();
        $this->assertSame('foo', $compiler->getPath());
    }

    public function testRawTagsCanBeSetToLegacyValues()
    {
        $compiler = new BladeCompiler($this->getFiles(), __DIR__);
        $compiler->setEchoFormat('%s');

        $this->assertSame('<?php echo e($name); ?>', $compiler->compileString('{{{ $name }}}'));
        $this->assertSame('<?php echo $name; ?>', $compiler->compileString('{{ $name }}'));
        $this->assertSame('<?php echo $name; ?>', $compiler->compileString('{{
            $name
        }}'));
    }

    /**
     * @param  string  $content
     * @param  string  $compiled
     */
    #[DataProvider('appendViewPathDataProvider')]
    public function testIncludePathToTemplate($content, $compiled)
    {
        $compiler = new BladeCompiler($files = $this->getFiles(), __DIR__);
        $files->shouldReceive('get')->once()->with('foo')->andReturn($content);
        $files->shouldReceive('exists')->once()->with(__DIR__)->andReturn(true);
        $files->shouldReceive('exists')->once()->with(__DIR__.'/'.hash('xxh128', 'v2foo').'.php')->andReturn(false);
        $files->shouldReceive('put')->once()->with(__DIR__.'/'.hash('xxh128', 'v2foo').'.php', $compiled);

        $compiler->compile('foo');
    }

    /**
     * @return array
     */
    public static function appendViewPathDataProvider()
    {
        return [
            'No PHP blocks' => [
                'Hello World',
                'Hello World<?php /**PATH foo ENDPATH**/ ?>',
            ],
            'Single PHP block without closing ?>' => [
                '<?php echo $path',
                '<?php echo $path ?><?php /**PATH foo ENDPATH**/ ?>',
            ],
            'Ending PHP block.' => [
                'Hello world<?php echo $path ?>',
                'Hello world<?php echo $path ?><?php /**PATH foo ENDPATH**/ ?>',
            ],
            'Ending PHP block without closing ?>' => [
                'Hello world<?php echo $path',
                'Hello world<?php echo $path ?><?php /**PATH foo ENDPATH**/ ?>',
            ],
            'PHP block between content.' => [
                'Hello world<?php echo $path ?>Hi There',
                'Hello world<?php echo $path ?>Hi There<?php /**PATH foo ENDPATH**/ ?>',
            ],
            'Multiple PHP blocks.' => [
                'Hello world<?php echo $path ?>Hi There<?php echo $path ?>Hello Again',
                'Hello world<?php echo $path ?>Hi There<?php echo $path ?>Hello Again<?php /**PATH foo ENDPATH**/ ?>',
            ],
            'Multiple PHP blocks without closing ?>' => [
                'Hello world<?php echo $path ?>Hi There<?php echo $path',
                'Hello world<?php echo $path ?>Hi There<?php echo $path ?><?php /**PATH foo ENDPATH**/ ?>',
            ],
            'Short open echo tag' => [
                'Hello world<?= echo $path',
                'Hello world<?= echo $path ?><?php /**PATH foo ENDPATH**/ ?>',
            ],
            'Echo XML declaration' => [
                '<?php echo \'<?xml version="1.0" encoding="UTF-8"?>\';',
                '<?php echo \'<?xml version="1.0" encoding="UTF-8"?>\'; ?><?php /**PATH foo ENDPATH**/ ?>',
            ],
        ];
    }

    public function testDontIncludeEmptyPath()
    {
        $compiler = new BladeCompiler($files = $this->getFiles(), __DIR__);
        $files->shouldReceive('get')->once()->with('')->andReturn('Hello World');
        $files->shouldReceive('exists')->once()->with(__DIR__)->andReturn(true);
        $files->shouldReceive('exists')->once()->with(__DIR__.'/'.hash('xxh128', 'v2').'.php')->andReturn(false);
        $files->shouldReceive('put')->once()->with(__DIR__.'/'.hash('xxh128', 'v2').'.php', 'Hello World');
        $compiler->setPath('');
        $compiler->compile();
    }

    public function testDontIncludeNullPath()
    {
        $compiler = new BladeCompiler($files = $this->getFiles(), __DIR__);
        $files->shouldReceive('get')->once()->with(null)->andReturn('Hello World');
        $files->shouldReceive('exists')->once()->with(__DIR__)->andReturn(true);
        $files->shouldReceive('exists')->once()->with(__DIR__.'/'.hash('xxh128', 'v2').'.php')->andReturn(false);
        $files->shouldReceive('put')->once()->with(__DIR__.'/'.hash('xxh128', 'v2').'.php', 'Hello World');
        $compiler->setPath(null);
        $compiler->compile();
    }

    public function testShouldStartFromStrictTypesDeclaration()
    {
        $compiler = new BladeCompiler($files = $this->getFiles(), __DIR__);
        $strictTypeDecl = "<?php\ndeclare(strict_types = 1);";
        $this->assertSame(substr($compiler->compileString("<?php\ndeclare(strict_types = 1);\nHello World"),
            0, strlen($strictTypeDecl)), $strictTypeDecl);
    }

    public function testComponentAliasesCanBeConventionallyDetermined()
    {
        $compiler = new BladeCompiler($files = $this->getFiles(), __DIR__);

        $compiler->component('App\Foo\Bar');
        $this->assertEquals(['bar' => 'App\Foo\Bar'], $compiler->getClassComponentAliases());

        $compiler = new BladeCompiler($files = $this->getFiles(), __DIR__);

        $compiler->component('App\Foo\Bar', null, 'prefix');
        $this->assertEquals(['prefix-bar' => 'App\Foo\Bar'], $compiler->getClassComponentAliases());

        $compiler = new BladeCompiler($files = $this->getFiles(), __DIR__);

        $compiler->component('App\View\Components\Forms\Input');
        $this->assertEquals(['forms:input' => 'App\View\Components\Forms\Input'], $compiler->getClassComponentAliases());

        $compiler = new BladeCompiler($files = $this->getFiles(), __DIR__);

        $compiler->component('App\View\Components\Forms\Input', null, 'prefix');
        $this->assertEquals(['prefix-forms:input' => 'App\View\Components\Forms\Input'], $compiler->getClassComponentAliases());
    }

    public function testAnonymousComponentNamespacesCanBeStored()
    {
        $compiler = new BladeCompiler($files = $this->getFiles(), __DIR__);

        $compiler->anonymousComponentNamespace(' public/frontend ', 'frontend');
        $this->assertEquals(['frontend' => 'public.frontend'], $compiler->getAnonymousComponentNamespaces());

        $compiler = new BladeCompiler($files = $this->getFiles(), __DIR__);

        $compiler->anonymousComponentNamespace('public/frontend/', 'frontend');
        $this->assertEquals(['frontend' => 'public.frontend'], $compiler->getAnonymousComponentNamespaces());

        $compiler = new BladeCompiler($files = $this->getFiles(), __DIR__);

        $compiler->anonymousComponentNamespace('/admin/components', 'admin');
        $this->assertEquals(['admin' => 'admin.components'], $compiler->getAnonymousComponentNamespaces());

        // Test directory is automatically inferred from the prefix if not given.
        $compiler = new BladeCompiler($files = $this->getFiles(), __DIR__);

        $compiler->anonymousComponentNamespace('frontend');
        $this->assertEquals(['frontend' => 'frontend'], $compiler->getAnonymousComponentNamespaces());

        // Test that the prefix can also contain dots.
        $compiler = new BladeCompiler($files = $this->getFiles(), __DIR__);

        $compiler->anonymousComponentNamespace('frontend/auth', 'frontend.auth');
        $this->assertEquals(['frontend.auth' => 'frontend.auth'], $compiler->getAnonymousComponentNamespaces());
    }

    protected function getFiles()
    {
        return m::mock(Filesystem::class);
    }
}
