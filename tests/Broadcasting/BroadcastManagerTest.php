<?php

namespace Illuminate\Tests\Broadcasting;

use Illuminate\Broadcasting\BroadcastManager;
use Illuminate\Config\Repository;
use Illuminate\Container\Container;
use PHPUnit\Framework\TestCase;

class BroadcastManagerTest extends TestCase
{
    protected function getManager(array $config = []): BroadcastManager
    {
        $config = array_merge([
            'broadcasting' => [
                'default' => 'null',
                'connections' => [
                    'null' => ['driver' => 'null'],
                ],
            ],
        ], $config);

        $app = new Container;
        $app->singleton('config', fn () => new Repository($config));

        return new BroadcastManager($app);
    }

    public function testDriverCanResolveBackedEnum(): void
    {
        $manager = $this->getManager();

        $driver1 = $manager->driver(BroadcastDriverName::NullDriver);
        $driver2 = $manager->driver('null');

        $this->assertSame($driver1, $driver2);
    }

    public function testConnectionCanResolveBackedEnum(): void
    {
        $manager = $this->getManager();

        $driver1 = $manager->connection(BroadcastDriverName::NullDriver);
        $driver2 = $manager->connection('null');

        $this->assertSame($driver1, $driver2);
    }

    public function testSetDefaultDriverAcceptsBackedEnum(): void
    {
        $manager = $this->getManager();

        $manager->setDefaultDriver(BroadcastDriverName::NullDriver);

        $this->assertSame('null', $manager->getDefaultDriver());
    }

    public function testPurgeAcceptsBackedEnum(): void
    {
        $manager = $this->getManager();

        $driver1 = $manager->driver(BroadcastDriverName::NullDriver);
        $manager->purge(BroadcastDriverName::NullDriver);
        $driver2 = $manager->driver(BroadcastDriverName::NullDriver);

        $this->assertNotSame($driver1, $driver2);
    }
}

enum BroadcastDriverName: string
{
    case NullDriver = 'null';
}
