<?php

namespace Illuminate\Tests\Bus;

use __PHP_Incomplete_Class;
use Aws\DynamoDb\DynamoDbClient;
use Illuminate\Bus\BatchFactory;
use Illuminate\Bus\DynamoBatchRepository;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class DynamoBatchRepositoryTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();

        parent::tearDown();
    }

    public function testOptionsAreUnserializedWithoutRestrictionsByDefault()
    {
        $repository = $this->createRepository();

        $options = ['name' => 'test', 'callback' => new DynamoBatchRepositoryTestCallback];

        $this->assertEquals($options, $repository->unserializeValue(serialize($options)));
    }

    public function testOptionsUnserializationCanBeRestricted()
    {
        $repository = $this->createRepository(serializableClasses: false);

        $unserialized = $repository->unserializeValue(serialize(['callback' => new DynamoBatchRepositoryTestCallback]));

        $this->assertInstanceOf(__PHP_Incomplete_Class::class, $unserialized['callback']);
    }

    public function testOptionsUnserializationAllowsWhitelistedClasses()
    {
        $repository = $this->createRepository(serializableClasses: [DynamoBatchRepositoryTestCallback::class]);

        $options = ['callback' => new DynamoBatchRepositoryTestCallback];

        $this->assertEquals($options, $repository->unserializeValue(serialize($options)));
    }

    protected function createRepository($serializableClasses = null)
    {
        return new DynamoBatchRepositoryUnserializeStub(
            m::mock(BatchFactory::class),
            m::mock(DynamoDbClient::class),
            'test',
            'job_batches',
            ttl: null,
            ttlAttribute: null,
            serializableClasses: $serializableClasses,
        );
    }
}

class DynamoBatchRepositoryUnserializeStub extends DynamoBatchRepository
{
    public function unserializeValue($serialized)
    {
        return $this->unserialize($serialized);
    }
}

class DynamoBatchRepositoryTestCallback
{
    //
}
