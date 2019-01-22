<?php

namespace Illuminate\Tests\Database;

use Mockery;
use PHPUnit\Framework\TestCase;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\Pivot;

class DatabaseEloquentCollectionQueueableTest extends TestCase
{
    public function tearDown()
    {
        Mockery::close();
    }

    public function testSerializesPivotsEntitiesId()
    {
        $spy = Mockery::spy(Pivot::class);

        $c = new Collection([$spy]);

        $c->getQueueableIds();

        $spy->shouldHaveReceived()
            ->getQueueableId()
            ->once();
    }

    public function testSerializesModelEntitiesById()
    {
        $spy = Mockery::spy(Model::class);

        $c = new Collection([$spy]);

        $c->getQueueableIds();

        $spy->shouldHaveReceived()
            ->getQueueableId()
            ->once();
    }

    /**
     * @throws \Exception
     */
    public function testJsonSerializationOfCollectionQueueableIdsWorks()
    {
        // When the ID of a Model is binary instead of int or string, the Collection
        // serialization + JSON encoding breaks because of UTF-8 issues. Encoding
        // of a QueueableCollection must favor QueueableEntity::queueableId().
        $mock = Mockery::mock(Model::class, [
            'getKey' => random_bytes(10),
            'getQueueableId' => 'mocked',
        ]);

        $c = new Collection([$mock]);

        $payload = [
            'ids' => $c->getQueueableIds(),
        ];

        $this->assertTrue(
            json_encode($payload) !== false,
            'EloquentCollection is not using the QueueableEntity::queueableId() method.'
        );
    }
}
