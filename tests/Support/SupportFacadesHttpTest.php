<?php

namespace Illuminate\Tests\Support;

use Illuminate\Container\Container;
use Illuminate\Http\Client\Factory;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\TestCase;

class SupportFacadesHttpTest extends TestCase
{
    protected $app;

    protected function setUp(): void
    {
        $this->app = new Container;
        Facade::setFacadeApplication($this->app);
    }

    public function testFacadeRootIsNotSharedByDefault(): void
    {
        $this->assertNotSame(Http::getFacadeRoot(), $this->app->make(Factory::class));
    }

    public function testFacadeRootIsSharedWhenFaked(): void
    {
        Http::fake([
            'https://laravel.com' => Http::response('OK!'),
        ]);

        $factory = $this->app->make(Factory::class);
        $this->assertSame('OK!', $factory->get('https://laravel.com')->body());
    }

    public function testFacadeRootIsSharedWhenFakedWithSequence(): void
    {
        Http::fakeSequence('laravel.com/*')->push('OK!');

        $factory = $this->app->make(Factory::class);
        $this->assertSame('OK!', $factory->get('https://laravel.com')->body());
    }

    public function testFacadeRootIsSharedWhenStubbingUrls(): void
    {
        Http::stubUrl('laravel.com', Http::response('OK!'));

        $factory = $this->app->make(Factory::class);
        $this->assertSame('OK!', $factory->get('https://laravel.com')->body());
    }

    public function testFacadeRootIsSharedWhenEnforcingFaking(): void
    {
        $client = Http::preventStrayRequests();

        $this->assertSame($client, $this->app->make(Factory::class));
    }
}
