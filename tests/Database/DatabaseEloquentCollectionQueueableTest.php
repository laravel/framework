<?php

namespace Illuminate\Tests\Database;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Pivot;
use JMac\Testing\Double;
use JMac\Testing\Matching\Argument;
use PHPUnit\Framework\TestCase;

class DatabaseEloquentCollectionQueueableTest extends TestCase
{
    public function testSerializesPivotsEntitiesId()
    {
        $spy = Double::for(Pivot::class);

        $c = new Collection([$spy]);

        $c->getQueueableIds();

        $spy->received('getQueueableId')->with(Argument::none())->times(1);
    }

    public function testSerializesModelEntitiesById()
    {
        $spy = Double::for(Model::class);

        $c = new Collection([$spy]);

        $c->getQueueableIds();

        $spy->received('getQueueableId')->with(Argument::none())->times(1);
    }

    /**
     * @throws \Exception
     */
    public function testJsonSerializationOfCollectionQueueableIdsWorks()
    {
        // When the ID of a Model is binary instead of int or string, the Collection
        // serialization + JSON encoding breaks because of UTF-8 issues. Encoding
        // of a QueueableCollection must favor QueueableEntity::queueableId().
        $mock = Double::for(Model::class);
        $mock->allows('getKey')->returns(random_bytes(10));
        $mock->allows('getQueueableId')->returns('mocked');

        $c = new Collection([$mock]);

        $payload = [
            'ids' => $c->getQueueableIds(),
        ];

        $this->assertNotFalse(
            json_encode($payload),
            'EloquentCollection is not using the QueueableEntity::getQueueableId() method.'
        );
    }
}
