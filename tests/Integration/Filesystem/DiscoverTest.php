<?php

namespace Illuminate\Tests\Integration\Filesystem;

use const DIRECTORY_SEPARATOR as DS;
use Illuminate\Filesystem\Discover;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Mockery;
use Orchestra\Testbench\TestCase;
use ReflectionClass;
use Symfony\Component\Finder\SplFileInfo;

class DiscoverTest extends TestCase
{
    protected function file(string $path): Mockery\MockInterface
    {
        return tap(Mockery::mock(SplFileInfo::class), static function (Mockery\MockInterface $mock) use ($path): void {
            $mock->expects('getRealPath')->andReturn(
                Str::of(realpath(__DIR__.'/../../../src/Illuminate/'))
                    ->append(DS.$path)
                    ->replace(['\\', '/'], [DS, DS])
                    ->toString()
            );
        });
    }

    protected function mockAllFiles(): void
    {
        File::expects('allFiles')->with(realpath(__DIR__.'/../../src/Illuminate/'))->andReturn([
            $this->file('Support/Carbon.php'),
            $this->file('Support/Manager.php'),
            $this->file('Support/Traits/Tappable.php'),
            $this->file('Contracts/Auth/Guard.php'),
        ]);
    }

    public function testUsesDefaultAppPathAndNamespace(): void
    {
        $files = Mockery::mock($this->app->make('files'))->makePartial();
        $files->expects('files')->with($this->app->path('Examples'))->andReturn([]);

        $app = Mockery::mock($this->app)->makePartial();
        $app->instance('files', $files);

        (new Discover($app, 'Examples'))->all();

        $app->shouldHaveReceived('basePath');
        $app->shouldHaveReceived('path');
        $app->shouldHaveReceived('getNamespace');
    }

    public function testUsesCustomNamespace()
    {
        $this->mock('files')->expects('files')->with($this->app->basePath('foo'.DS.'Bar'))->andReturn([]);

        Discover::in('Bar')->atNamespace('Foo')->all();
    }

    public function testUsesCustomNamespaceWithBasePath()
    {
        $this->mock('files')->expects('files')->with($this->app->basePath('baz'.DS.'Bar'))->andReturn([]);

        Discover::in('Bar')->atNamespace('Foo', 'baz')->all();
    }

    public function testUsesRecursive()
    {
        $this->mock('files')->expects('allFiles')->with($this->app->path('Examples'))->andReturn([]);

        Discover::in('Examples')->recursively()->all();
    }

    public function testFiltersByInstantiableClasses()
    {
        $app = Mockery::mock($this->app)->makePartial();
        $app->expects('basePath')->andReturn(realpath(__DIR__.'/../../../src'));
        $app->expects('path')->andReturn(realpath(__DIR__.'/../../../src/Illuminate'));
        $app->expects('getNamespace')->andReturn('Illuminate\\');

        $this->mock('files')
            ->expects('allFiles')
            ->with(realpath(__DIR__.'/../../../src/Illuminate/Support'))
            ->andReturn([
                $this->file('Support/helpers.php'),
                $this->file('Support/Manager.php'),
                $this->file('Support/Stringable.php'),
                $this->file('Support/Traits/Tappable.php'),
            ]);

        $classes = (new Discover($app, 'Support'))->recursively()->all();

        $this->assertCount(1, $classes);
        $this->assertTrue($classes->has(\Illuminate\Support\Stringable::class));
        $this->assertInstanceOf(ReflectionClass::class, $classes->first());
    }

    public function testFiltersByNonInterfaces()
    {
        $app = Mockery::mock($this->app)->makePartial();

        $app->expects('basePath')->andReturn(realpath(__DIR__.'/../../../src'));
        $app->expects('path')->andReturn(realpath(__DIR__.'/../../../src/Illuminate'));
        $app->expects('getNamespace')->andReturn('Illuminate\\');

        $this->mock('files')
            ->expects('files')
            ->with(realpath(__DIR__.'/../../../src/Illuminate/Auth/Passwords'))
            ->andReturn([
                $this->file('Auth/Passwords/PasswordBroker.php'),
                $this->file('Auth/Passwords/TokenRepositoryInterface.php'),
            ]);

        $classes = (new Discover($app, 'Auth'.DS.'Passwords'))->all();

        $this->assertCount(1, $classes);
        $this->assertTrue($classes->has(\Illuminate\Auth\Passwords\PasswordBroker::class));
    }
}
