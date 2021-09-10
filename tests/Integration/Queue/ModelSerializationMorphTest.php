<?php

namespace Illuminate\Tests\Integration\Queue;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\Relation;

/**
 * @group integration
 */
class ModelSerializationMorphTest extends ModelSerializationTest
{
    protected function setUp(): void
    {
        parent::setUp();

        Relation::morphMap([
            'modelBoot' => ModelBootTestWithTraitInitialization::class,
            'testUser' => ModelSerializationTestUser::class,
            'customerUser' => ModelSerializationTestCustomUser::class,
            'order' => Order::class,
            'line' => Line::class,
            'product' => Product::class,
            'user' => User::class,
            'role' => Role::class,
            'roleUser' => RoleUser::class,
        ]);
    }

    public function testItCanUnserializeWithMorphs()
    {
        $user = ModelSerializationTestUser::create([
            'email' => 'nuno@laravel.com',
        ]);

        $serialized = serialize(new ModelSerializationTestClass($user));

        $unSerialized = unserialize($serialized);

        $this->assertSame('nuno@laravel.com', $unSerialized->user->email);
    }

    public function test_model_serialization_structure()
    {
        $user = ModelSerializationTestUser::create([
            'email' => 'nuno@laravel.com',
        ]);

        $serialized = serialize(new CollectionSerializationTestClass($user));

        $this->assertSame(
            'O:67:"Illuminate\Tests\Integration\Queue\CollectionSerializationTestClass":1:{s:5:"users";O:45:"Illuminate\Contracts\Database\ModelIdentifier":4:{s:5:"class";s:8:"testUser";s:2:"id";i:1;s:9:"relations";a:0:{}s:10:"connection";s:9:"testbench";}}',
            $serialized
        );
    }

    public function test_model_serialization_collection_structure()
    {
        $user = ModelSerializationTestUser::create([
            'email' => 'nuno@laravel.com',
        ]);

        $serialized = serialize(new CollectionSerializationTestClass(new Collection([$user, $user])));

        $this->assertSame(
            'O:67:"Illuminate\Tests\Integration\Queue\CollectionSerializationTestClass":1:{s:5:"users";O:45:"Illuminate\Contracts\Database\ModelIdentifier":4:{s:5:"class";s:8:"testUser";s:2:"id";a:2:{i:0;i:1;i:1;i:1;}s:9:"relations";a:0:{}s:10:"connection";s:9:"testbench";}}',
            $serialized
        );
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        Relation::morphMap([], false);
    }
}
