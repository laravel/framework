<?php

namespace Illuminate\Tests\Queue;

use Closure;
use Illuminate\Container\Container;
use Illuminate\Queue\Attributes\Connection;
use Illuminate\Queue\Attributes\Queue;
use Illuminate\Support\Traits\ReadsClassAttributes;
use PHPUnit\Framework\TestCase;

class QueueAttributesTest extends TestCase
{
    public function test_queue_attribute_normalizes_backed_enum_to_string()
    {
        $attribute = new Queue(QueueAttributeBackedEnum::DEFAULT);

        $this->assertSame('default', $attribute->queue);
    }

    public function test_queue_attribute_normalizes_unit_enum_to_string()
    {
        $attribute = new Queue(QueueAttributeUnitEnum::High);

        $this->assertSame('High', $attribute->queue);
    }

    public function test_queue_attribute_keeps_string_as_string()
    {
        $attribute = new Queue('high');

        $this->assertSame('high', $attribute->queue);
    }

    public function test_connection_attribute_normalizes_backed_enum_to_string()
    {
        $attribute = new Connection(ConnectionAttributeBackedEnum::REDIS);

        $this->assertSame('redis', $attribute->connection);
    }

    public function test_connection_attribute_normalizes_unit_enum_to_string()
    {
        $attribute = new Connection(ConnectionAttributeUnitEnum::Redis);

        $this->assertSame('Redis', $attribute->connection);
    }

    public function test_connection_attribute_keeps_string_as_string()
    {
        $attribute = new Connection('redis');

        $this->assertSame('redis', $attribute->connection);
    }

    public function test_queue_attribute_keeps_closure_unresolved()
    {
        $attribute = new Queue(fn () => 'high');

        $this->assertInstanceOf(Closure::class, $attribute->queue);
    }

    public function test_connection_attribute_keeps_closure_unresolved()
    {
        $attribute = new Connection(fn () => 'redis');

        $this->assertInstanceOf(Closure::class, $attribute->connection);
    }

    public function test_queue_attribute_closure_is_resolved_when_read()
    {
        $harness = new ReadsQueueAttributesTestHarness;

        $this->assertSame('high', $harness->extract(new Queue(fn () => 'high')));
    }

    public function test_queue_attribute_closure_result_is_normalized_from_enum()
    {
        $harness = new ReadsQueueAttributesTestHarness;

        $this->assertSame('high', $harness->extract(new Queue(fn () => QueueAttributeBackedEnum::HIGH)));
    }

    public function test_queue_attribute_closure_receives_container_instance()
    {
        $harness = new ReadsQueueAttributesTestHarness;

        $received = null;

        $harness->extract(new Queue(function ($app) use (&$received) {
            $received = $app;

            return 'high';
        }));

        $this->assertSame(Container::getInstance(), $received);
    }

    public function test_connection_attribute_closure_is_resolved_when_read()
    {
        $harness = new ReadsQueueAttributesTestHarness;

        $this->assertSame('redis', $harness->extract(new Connection(fn () => ConnectionAttributeBackedEnum::REDIS)));
    }
}

class ReadsQueueAttributesTestHarness
{
    use ReadsClassAttributes;

    public function extract($instance)
    {
        return $this->extractAttributeValue($instance);
    }
}

enum QueueAttributeBackedEnum: string
{
    case DEFAULT = 'default';
    case HIGH = 'high';
}

enum QueueAttributeUnitEnum
{
    case High;
    case Default;
}

enum ConnectionAttributeBackedEnum: string
{
    case REDIS = 'redis';
    case SQS = 'sqs';
}

enum ConnectionAttributeUnitEnum
{
    case Redis;
    case Sqs;
}
