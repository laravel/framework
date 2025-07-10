<?php

namespace Illuminate\Tests\Foundation\Configuration;

use Illuminate\Config\Repository as Config;
use Illuminate\Container\Container;
use Illuminate\Foundation\Concerns\ResolvesDumpSource;
use PHPUnit\Framework\TestCase;

class ResolvesDumpSourceTest extends TestCase
{
    protected function setUp(): void
    {
        $container = Container::setInstance(new Container);

        $container->singleton('config', function () {
            return $this->createConfig();
        });
    }

    protected function getEnvironmentSetUp($app)
    {
        // Set config values to simulate Docker path mapping
        $app['config']->set('app.local_sites_path', '/path/to/my-app');
        $app['config']->set('app.remote_sites_path', '/var/www/html');
    }

    public function testItMapsRemotePathToLocalPath()
    {
        $mock = new class
        {
            use ResolvesDumpSource;

            public function testMap($path)
            {
                return $this->mapToLocalPath($path);
            }
        };

        $originalPath = '/var/www/html/app/Http/Controllers/HomeController.php';
        $expectedPath = '/path/to/my-app/app/Http/Controllers/HomeController.php';

        $mapped = $mock->testMap($originalPath);
        $this->assertEquals($expectedPath, $mapped);
    }

    public function testItReturnsOriginalPathWhenNoConfigIsSet()
    {
        config()->set('app.local_sites_path', null);
        config()->set('app.remote_sites_path', null);

        $mock = new class
        {
            use ResolvesDumpSource;

            public function testMap($path)
            {
                return $this->mapToLocalPath($path);
            }
        };

        $path = '/var/www/html/app/Console/Kernel.php';

        $this->assertEquals($path, $mock->testMap($path));
    }

    public function testItReturnsOriginalPathWhenPathDoesNotMatchRemote()
    {
        $mock = new class
        {
            use ResolvesDumpSource;

            public function testMap($path)
            {
                return $this->mapToLocalPath($path);
            }
        };

        $nonMatchingPath = '/srv/other/path/SomeFile.php';

        $this->assertEquals($nonMatchingPath, $mock->testMap($nonMatchingPath));
    }

    /**
     * Create a new config repository instance.
     *
     * @return \Illuminate\Config\Repository
     */
    protected function createConfig()
    {
        return new Config([
            'app' => [
                'remote_sites_path' => '/var/www/html',
                'local_sites_path' => '/path/to/my-app',
            ],
        ]);
    }
}
