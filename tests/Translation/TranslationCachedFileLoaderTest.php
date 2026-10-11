<?php

namespace Illuminate\Tests\Translation;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Translation\CachedFileLoader;
use Mockery;
use PHPUnit\Framework\TestCase;

class TranslationCachedFileLoaderTest extends TestCase
{
    public function testLoadMethodReturnsCachedLinesWithoutReadingFiles()
    {
        $files = Mockery::mock(Filesystem::class);
        $files->shouldNotReceive('exists');
        $files->shouldNotReceive('getRequire');
        $files->shouldNotReceive('get');

        $loader = new CachedFileLoader($files, __DIR__, [
            'en' => [
                '*' => [
                    '*' => ['Hello' => 'Hello'],
                    'messages' => ['foo' => 'bar'],
                    'nested/messages' => ['baz' => 'qux'],
                ],
                'package' => [
                    'messages' => ['foo' => 'package-bar'],
                ],
            ],
        ]);

        $this->assertSame(['Hello' => 'Hello'], $loader->load('en', '*', '*'));
        $this->assertSame(['foo' => 'bar'], $loader->load('en', 'messages', '*'));
        $this->assertSame(['foo' => 'bar'], $loader->load('en', 'messages'));
        $this->assertSame(['baz' => 'qux'], $loader->load('en', 'nested/messages', '*'));
        $this->assertSame(['foo' => 'package-bar'], $loader->load('en', 'messages', 'package'));
    }

    public function testLoadMethodReturnsEmptyArrayForMissingGroupsOfCachedLocales()
    {
        $files = Mockery::mock(Filesystem::class);
        $files->shouldNotReceive('exists');

        $loader = new CachedFileLoader($files, __DIR__, [
            'en' => ['*' => ['messages' => ['foo' => 'bar']]],
        ]);

        $this->assertSame([], $loader->load('en', 'missing', '*'));
        $this->assertSame([], $loader->load('en', '*', '*'));
        $this->assertSame([], $loader->load('en', 'messages', 'package'));
    }

    public function testLoadMethodFallsBackToFilesForLocalesThatAreNotCached()
    {
        $files = Mockery::mock(Filesystem::class);
        $files->expects('exists')->with(__DIR__.'/fr/messages.php')->andReturn(true);
        $files->expects('getRequire')->with(__DIR__.'/fr/messages.php')->andReturn(['foo' => 'le bar']);

        $loader = new CachedFileLoader($files, __DIR__, [
            'en' => ['*' => ['messages' => ['foo' => 'bar']]],
        ]);

        $this->assertSame(['foo' => 'le bar'], $loader->load('fr', 'messages', '*'));
    }
}
