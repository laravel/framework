<?php

namespace Illuminate\Tests\Queue;

use Mockery as m;
use PHPUnit\Framework\TestCase;
use Illuminate\Queue\Failed\Manager;
use Illuminate\Queue\Failed\NullFailedJobProvider;
use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Queue\Failed\DatabaseFailedJobProvider;
use Illuminate\Queue\Failed\FailedJobProviderInterface;

class QueueFailedManagerTest extends TestCase
{
    public function tearDown()
    {
        m::close();
    }

    public function testCreateManagerWithDefaultProviders()
    {
        $app = [
            'config' => [
                'queue.failed.provider' => 'null',
                'queue.failed.database' => ['connection' => 'sqlite', 'table' => 'failed_jobs'],
            ],
            'db' => m::mock(ConnectionResolverInterface::class),
        ];

        $manager = new Manager($app);

        $this->assertInstanceOf(NullFailedJobProvider::class, $manager->provider('null'));
        $this->assertInstanceOf(DatabaseFailedJobProvider::class, $manager->provider('database'));
    }

    public function testReturnConfiguredProvider()
    {
        $app = [
            'config' => [
                'queue.failed.provider' => 'database',
                'queue.failed.database' => ['connection' => 'sqlite', 'table' => 'failed_jobs'],
            ],
            'db' => m::mock(ConnectionResolverInterface::class),
        ];

        $manager = new Manager($app);

        $this->assertInstanceOf(DatabaseFailedJobProvider::class, $manager->provider());
    }

    public function testFallbackToNullProviderIfNotConfiguredProperly()
    {
        $app = [
            'config' => [],
        ];

        $manager = new Manager($app);

        $this->assertInstanceOf(NullFailedJobProvider::class, $manager->provider());
    }

    public function testAddCustomProvider()
    {
        $app = [
            'config' => [
                'queue.failed.provider' => 'custom',
            ],
        ];

        $customProvider = m::mock(FailedJobProviderInterface::class);

        $manager = new Manager($app);
        $manager->addProvider('custom', function () use ($customProvider) {
            return $customProvider;
        });

        $this->assertSame($customProvider, $manager->provider('custom'));
        $this->assertSame($customProvider, $manager->provider());
    }

    public function testReturnNullProviderIfCustomIsNotAdded()
    {
        $app = [
            'config' => [
                'queue.failed.provider' => 'custom',
            ],
        ];

        $manager = new Manager($app);

        $this->assertInstanceOf(NullFailedJobProvider::class, $manager->provider('custom'));
        $this->assertInstanceOf(NullFailedJobProvider::class, $manager->provider());
    }
}
