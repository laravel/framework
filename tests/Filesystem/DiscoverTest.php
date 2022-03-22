<?php

namespace Illuminate\Tests\Filesystem;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Filesystem\Discover;
use Illuminate\Filesystem\Filesystem;
use Mockery;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Finder\SplFileInfo;
use function realpath;
use const DIRECTORY_SEPARATOR as DS;

class DiscoverTest extends TestCase
{
    protected $files;
    protected $app;
    protected $discover;

    protected function setUp(): void
    {
        $this->app = Mockery::mock(Application::class);
        $this->files = Mockery::mock(Filesystem::class);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        Mockery::close();
    }

    protected function returnRealSupportFiles()
    {
        $this->app->expects('basePath')->withNoArgs()->andReturn(realpath(__DIR__.'/../../src'));
        $this->app->expects('path')->withNoArgs()->andReturn('Illuminate');
        $this->app->expects('getNamespace')->withNoArgs()->andReturn('Illuminate\\');
        $this->app->expects('make')->with('files')->andReturn($this->files);
    }

    public function testUsesAppDefaultPathsAndNamespaces()
    {
        $this->app->expects('basePath')->withNoArgs()->andReturn('/projects/laravel');
        $this->app->expects('path')->withNoArgs()->andReturn('app');
        $this->app->expects('getNamespace')->withNoArgs()->andReturn('App\\');
        $this->app->expects('make')->with('files')->andReturn($this->files);

        $this->files->expects('files')->with('/projects/laravel'.DS.'app')->andReturn([]);

        $this->assertEmpty((new Discover($this->app))->all());
    }

    public function testUsesDifferentPathAndNamespace()
    {
        $this->app->expects('basePath')->withNoArgs()->andReturn('/projects/laravel');
        $this->app->expects('path')->never();
        $this->app->expects('getNamespace')->never();
        $this->app->expects('make')->with('files')->andReturn($this->files);

        $this->files->expects('files')->with('/projects/laravel'.DS.'foo')->andReturn([]);

        $this->assertEmpty((new Discover($this->app))->atNamespace('foo', 'Bar')->all());
    }

    public function testRetrievesClassesInFirstLevelByDefault()
    {
        $this->app->expects('basePath')->withNoArgs()->andReturn('/projects/laravel');
        $this->app->expects('path')->withNoArgs()->andReturn('app');
        $this->app->expects('getNamespace')->withNoArgs()->andReturn('App\\');
        $this->app->expects('make')->with('files')->andReturn($this->files);

        $this->files->expects('files')->with('/projects/laravel'.DS.'app')->andReturn([]);

        $this->assertEmpty((new Discover($this->app))->all());
    }

    public function testRetrievesClassesRecursively()
    {
        $this->app->expects('basePath')->withNoArgs()->andReturn('/projects/laravel');
        $this->app->expects('path')->withNoArgs()->andReturn('app');
        $this->app->expects('getNamespace')->withNoArgs()->andReturn('App\\');
        $this->app->expects('make')->with('files')->andReturn($this->files);

        $this->files->expects('allFiles')->with('/projects/laravel'.DS.'app')->andReturn([]);

        $this->assertEmpty((new Discover($this->app))->recursively()->all());
    }

    public function testFiltersFilesByPath()
    {
        $this->app->expects('basePath')->withNoArgs()->andReturn('/projects/laravel');
        $this->app->expects('path')->withNoArgs()->andReturn('app');
        $this->app->expects('getNamespace')->withNoArgs()->andReturn('App\\');
        $this->app->expects('make')->with('files')->andReturn($this->files);

        $this->files->expects('files')
            ->with('/projects/laravel'.DS.'app'.DS.'FilterPath')
            ->andReturn([]);

        $this->assertEmpty((new Discover($this->app))->in('FilterPath')->all());
    }

    public function testFiltersFilesByPhpClasses()
    {
        $this->returnRealSupportFiles();

        $this->files->expects('files')
            ->with(realpath(__DIR__.'/../../src/Illuminate'.DS.'Support'))
            ->andReturn([
                new SplFileInfo(realpath(__DIR__.'/../../src/Illuminate'.DS.'Support'.DS.'helpers.php'), '', ''),
                new SplFileInfo(realpath(__DIR__.'/../../src/Illuminate'.DS.'Support'.DS.'Fluent.php'), '', ''),
                new SplFileInfo(realpath(__DIR__.'/../../src/Illuminate'.DS.'Support'.DS.'Stringable.php'), '', ''),
            ]);

        $files = (new Discover($this->app))->in('Support')->all();

        $this->assertCount(2, $files);
        $this->assertTrue($files->has(\Illuminate\Support\Fluent::class));
        $this->assertTrue($files->has(\Illuminate\Support\Stringable::class));
    }

    public function testRetrievesOnlyInstantiableClasses()
    {
        $this->returnRealSupportFiles();

        $this->files->expects('files')
            ->with(realpath(__DIR__.'/../../src/Illuminate'.DS.'Support'))
            ->andReturn([
                new SplFileInfo(realpath(__DIR__.'/../../src/Illuminate'.DS.'Support'.DS.'Manager.php'), '', ''),
                new SplFileInfo(realpath(__DIR__.'/../../src/Illuminate'.DS.'Support'.DS.'InteractsWithTime.php'), '', ''),
                new SplFileInfo(realpath(__DIR__.'/../../src/Illuminate'.DS.'Support'.DS.'Stringable.php'), '', ''),
            ]);

        $files = (new Discover($this->app))->in('Support')->all();

        $this->assertCount(1, $files);
        $this->assertTrue($files->has(\Illuminate\Support\Stringable::class));
    }
}
