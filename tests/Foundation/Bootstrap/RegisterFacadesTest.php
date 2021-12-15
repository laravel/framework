<?php

namespace Illuminate\Tests\Foundation\Bootstrap;

use Illuminate\Config\Repository as Config;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\AliasLoader;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Bootstrap\RegisterFacades;
use Illuminate\Foundation\PackageManifest;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades;
use Illuminate\Support\Js;
use Illuminate\Support\Str;
use Mockery as m;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class RegisterFacadesTest extends TestCase
{
    protected function setUp(): void
    {
        $this->app = m::mock(Application::class);

        $this->config = new Config();

        $this->app->shouldReceive('make')
            ->with('config')
            ->once()
            ->andReturn($this->config);

        $this->packageManifest = m::mock(PackageManifest::class);

        $this->app->shouldReceive('make')
            ->with(PackageManifest::class)
            ->once()
            ->andReturn($this->packageManifest);
    }

    protected function tearDown(): void
    {
        m::close();

        tap(AliasLoader::getInstance(), function ($loader) {
            $loader->setAliases([]);
            $loader->setRegistered(false);
        });
    }

    public function testCustomAliases()
    {
        $this->config->set('app.aliases', [
            'MyApp' => Facades\App::class,
        ]);

        $this->packageManifest->shouldReceive('aliases')
            ->once()
            ->andReturn([]);

        (new RegisterFacades())->bootstrap($this->app);

        $this->assertTrue(class_exists('MyApp'));
        $reflection = new ReflectionClass('MyApp');

        $this->assertSame(Facades\App::class, $reflection->getName());

        $this->assertFalse(class_exists('App'));
        $this->assertCount(1, AliasLoader::getInstance()->getAliases());
    }

    public function testPackageManifestAliases()
    {
        $this->config->set('app.aliases', []);

        $this->packageManifest->shouldReceive('aliases')
            ->once()
            ->andReturn([
                'MyPackageApp' => Facades\App::class,
            ]);

        (new RegisterFacades())->bootstrap($this->app);

        $this->assertTrue(class_exists('MyPackageApp'));
        $reflection = new ReflectionClass('MyPackageApp');

        $this->assertSame(Facades\App::class, $reflection->getName());

        $this->assertFalse(class_exists('App'));
        $this->assertCount(1, AliasLoader::getInstance()->getAliases());
    }

    public function testDefaultAliases()
    {
        $this->packageManifest->shouldReceive('aliases')
            ->once()
            ->andReturn([]);

        (new RegisterFacades())->bootstrap($this->app);

        foreach ($this->getDefaultAliases() as $alias => $abstract) {
            $this->assertTrue(class_exists($alias));

            $reflection = new ReflectionClass($alias);

            $this->assertSame($abstract, $reflection->getName());
        }

        $this->assertCount(39, AliasLoader::getInstance()->getAliases());
    }

    protected function getDefaultAliases()
    {
        return [
            'App' => Facades\App::class,
            'Arr' => Arr::class,
            'Artisan' => Facades\Artisan::class,
            'Auth' => Facades\Auth::class,
            'Blade' => Facades\Blade::class,
            'Broadcast' => Facades\Broadcast::class,
            'Bus' => Facades\Bus::class,
            'Cache' => Facades\Cache::class,
            'Config' => Facades\Config::class,
            'Cookie' => Facades\Cookie::class,
            'Crypt' => Facades\Crypt::class,
            'Date' => Facades\Date::class,
            'DB' => Facades\DB::class,
            'Eloquent' => Model::class,
            'Event' => Facades\Event::class,
            'File' => Facades\File::class,
            'Gate' => Facades\Gate::class,
            'Hash' => Facades\Hash::class,
            'Http' => Facades\Http::class,
            'Js' => Js::class,
            'Lang' => Facades\Lang::class,
            'Log' => Facades\Log::class,
            'Mail' => Facades\Mail::class,
            'Notification' => Facades\Notification::class,
            'Password' => Facades\Password::class,
            'Queue' => Facades\Queue::class,
            'RateLimiter' => Facades\RateLimiter::class,
            'Redirect' => Facades\Redirect::class,
            'Redis' => Facades\Redis::class,
            'Request' => Facades\Request::class,
            'Response' => Facades\Response::class,
            'Route' => Facades\Route::class,
            'Schema' => Facades\Schema::class,
            'Session' => Facades\Session::class,
            'Storage' => Facades\Storage::class,
            'Str' => Str::class,
            'URL' => Facades\URL::class,
            'Validator' => Facades\Validator::class,
            'View' => Facades\View::class,
        ];
    }
}
