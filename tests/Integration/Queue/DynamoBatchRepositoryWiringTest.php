<?php

namespace Illuminate\Tests\Integration\Queue;

use Illuminate\Bus\DynamoBatchRepository;
use Orchestra\Testbench\TestCase;
use ReflectionProperty;

class DynamoBatchRepositoryWiringTest extends TestCase
{
    public function test_serializable_classes_config_is_passed_to_the_repository()
    {
        $this->app['config']->set('queue.batching', [
            'driver' => 'dynamodb',
            'region' => 'us-west-2',
            'key' => 'key',
            'secret' => 'secret',
            'serializable_classes' => [self::class],
        ]);

        $repository = $this->app->make(DynamoBatchRepository::class);

        $this->assertSame(
            [self::class],
            (new ReflectionProperty($repository, 'serializableClasses'))->getValue($repository)
        );
    }

    public function test_serializable_classes_default_to_no_restrictions()
    {
        $this->app['config']->set('queue.batching', [
            'driver' => 'dynamodb',
            'region' => 'us-west-2',
            'key' => 'key',
            'secret' => 'secret',
        ]);

        $repository = $this->app->make(DynamoBatchRepository::class);

        $this->assertNull(
            (new ReflectionProperty($repository, 'serializableClasses'))->getValue($repository)
        );
    }
}
