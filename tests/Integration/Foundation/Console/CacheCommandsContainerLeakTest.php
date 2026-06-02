<?php

namespace Illuminate\Tests\Integration\Foundation\Console;

use Illuminate\Container\Container;
use Illuminate\Support\Facades\Route;
use Illuminate\Tests\Integration\Generators\TestCase;

class CacheCommandsContainerLeakTest extends TestCase
{
    protected $files = [
        'bootstrap/cache/config.php',
        'bootstrap/cache/routes-v7.php',
    ];

    public function testConfigCacheRestoresGlobalContainerInstance()
    {
        $original = Container::getInstance();

        $this->artisan('config:cache')->assertSuccessful();

        $this->assertSame(
            $original,
            Container::getInstance(),
            'config:cache leaked the secondary Application instance into Container::getInstance().'
        );
    }

    public function testRouteCacheRestoresGlobalContainerInstance()
    {
        Route::get('/cache-leak-test', fn () => 'ok');

        $original = Container::getInstance();

        $this->artisan('route:cache')->assertSuccessful();

        $this->assertSame(
            $original,
            Container::getInstance(),
            'route:cache leaked the secondary Application instance into Container::getInstance().'
        );
    }
}
