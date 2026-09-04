<?php

namespace Illuminate\Tests\Queue;

use Illuminate\Queue\Attributes\BackoffJitter;
use Illuminate\Queue\Attributes\Connection;
use Illuminate\Queue\Attributes\Queue;
use PHPUnit\Framework\TestCase;

class QueueAttributesTest extends TestCase
{
    public function test_backoff_jitter_attribute_defaults_to_the_default_ratio()
    {
        $attribute = new BackoffJitter;

        $this->assertSame(BackoffJitter::DEFAULT_RATIO, $attribute->ratio);
    }

    public function test_backoff_jitter_attribute_accepts_an_explicit_ratio()
    {
        $attribute = new BackoffJitter(0.5);

        $this->assertSame(0.5, $attribute->ratio);
    }

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
