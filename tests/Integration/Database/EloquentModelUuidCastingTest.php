<?php

namespace Illuminate\Tests\Integration\Database\EloquentModelUuidCastingTest;

use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Tests\Integration\Database\DatabaseTestCase;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

class EloquentModelUuidCastingTest extends DatabaseTestCase
{
    protected function defineDatabaseMigrationsAfterDatabaseRefreshed()
    {
        Schema::create('test_model1', function (Blueprint $table) {
            $table->increments('id');
            $table->uuid();
        });
    }

    public function testUuidsAreCustomCastable()
    {
        $user = TestModel1::create([
            'uuid' => '41828d7a-997c-4190-976a-ff2cf61f0b52',
        ]);

        $this->assertSame('41828d7a-997c-4190-976a-ff2cf61f0b52', $user->toArray()['uuid']);
        $this->assertInstanceOf(UuidInterface::class, $user->uuid);
    }

    public function testUuidsArrayAndJson()
    {
        $user = TestModel1::create([
            'uuid' => '41828d7a-997c-4190-976a-ff2cf61f0b52',
        ]);

        $expected = [
            'uuid' => '41828d7a-997c-4190-976a-ff2cf61f0b52',
            'id' => 1,
        ];

        $this->assertSame($expected, $user->toArray());
        $this->assertSame(json_encode($expected), $user->toJson());
    }

    public function testCustomUuidCastsAreComparedAsUuidsForUuidInstances()
    {
        $user = TestModel1::create([
            'uuid' => '41828d7a-997c-4190-976a-ff2cf61f0b52',
        ]);

        $user->uuid = Uuid::fromString('41828d7a-997c-4190-976a-ff2cf61f0b52');

        $this->assertArrayNotHasKey('uuid', $user->getDirty());
    }

    public function testCustomUuidCastsAreComparedAsUuidsForStringValues()
    {
        $user = TestModel1::create([
            'uuid' => '41828d7a-997c-4190-976a-ff2cf61f0b52',
        ]);

        $user->uuid = '41828d7a-997c-4190-976a-ff2cf61f0b52';

        $this->assertArrayNotHasKey('uuid', $user->getDirty());
    }
}

class TestModel1 extends Model
{
    public $table = 'test_model1';
    public $timestamps = false;
    protected $guarded = [];

    public $casts = [
        'uuid' => 'uuid',
    ];
}
