<?php

namespace Illuminate\Tests\Integration\Log;

use ErrorException;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Foundation\Auth\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Context;
use Orchestra\Testbench\Attributes\WithMigration;
use Orchestra\Testbench\Factories\UserFactory;
use Orchestra\Testbench\TestCase;
use RuntimeException;

#[WithMigration]
class ContextIntegrationTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_it_can_hydrate_null()
    {
        Context::hydrate(null);
        $this->assertEquals([], Context::all());
    }

    public function test_it_handles_eloquent()
    {
        $user = UserFactory::new()->create(['name' => 'Tim']);

        Context::add('model', $user);
        Context::add('number', 55);
        $dehydrated = Context::dehydrate();

        $this->assertSame([
            'data' => [
                'model' => 'O:45:"Illuminate\Contracts\Database\ModelIdentifier":5:{s:5:"class";s:31:"Illuminate\Foundation\Auth\User";s:2:"id";i:1;s:9:"relations";a:0:{}s:10:"connection";s:7:"testing";s:15:"collectionClass";N;}',
                'number' => 'i:55;',
            ],
            'hidden' => [],
        ], $dehydrated);

        Context::flush();
        $this->assertNull(Context::get('model'));
        $this->assertNull(Context::get('number'));

        Context::hydrate($dehydrated);
        $this->assertTrue($user->is(Context::get('model')));
        $this->assertNotSame($user, Context::get('model'));
        $this->assertSame(55, Context::get('number'));
    }

    public function test_it_ignores_deleted_models_when_hydrating()
    {
        $user = UserFactory::new()->create(['name' => 'Tim']);

        Context::add('model', $user);
        Context::add('number', 55);

        $dehydrated = Context::dehydrate();
        $user->delete();

        Context::flush();
        $this->assertNull(Context::get('model'));
        $this->assertNull(Context::get('number'));

        Context::hydrate($dehydrated);
        $this->assertNull(Context::get('model'));
        $this->assertSame(55, Context::get('number'));
    }

    public function test_it_ignores_deleted_models_within_collections_when_hydrating()
    {
        $user = UserFactory::new()->create(['name' => 'Tim']);

        Context::add('models', User::all());
        Context::add('number', 55);

        $dehydrated = Context::dehydrate();
        $user->delete();

        Context::flush();
        $this->assertNull(Context::get('model'));
        $this->assertNull(Context::get('number'));

        Context::hydrate($dehydrated);
        $this->assertInstanceOf(EloquentCollection::class, Context::get('models'));
        $this->assertCount(0, Context::get('models'));
        $this->assertSame(55, Context::get('number'));
    }

    public function test_it_throws_on_incomplete_classes()
    {
        $dehydrated = [
            'data' => [
                'model' => 'O:18:"App\MyContextClass":0:{}',
            ],
            'hidden' => [],
        ];

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Value is incomplete class: {"__PHP_Incomplete_Class_Name":"App\\\\MyContextClass"}');

        Context::hydrate($dehydrated);
    }

    public function test_it_throws_generic_unserialize_exceptions()
    {
        $dehydrated = [
            'data' => [
                'model' => 'bad data',
            ],
            'hidden' => [],
        ];

        $this->expectException(ErrorException::class);
        $this->expectExceptionMessage('unserialize(): Error at offset 0 of 8 bytes');

        Context::hydrate($dehydrated);
    }

    public function test_it_can_handle_unserialize_exceptions_manually()
    {
        $dehydrated = [
            'data' => [
                'model' => 'bad data',
            ],
            'hidden' => [
                'other' => 'more bad data',
            ],
        ];

        Context::handleUnserializeExceptionsUsing(function ($e, $key, $value, $hidden) {
            if ($key === 'model') {
                $this->assertSame('bad data', $value);
                $this->assertFalse($hidden);

                return 'replaced value 1';
            } else {
                $this->assertSame('more bad data', $value);
                $this->assertTrue($hidden);

                return 'replaced value 2';
            }
        });
        Context::hydrate($dehydrated);

        $this->assertSame('replaced value 1', Context::get('model'));
        $this->assertSame('replaced value 2', Context::getHidden('other'));

        Context::handleUnserializeExceptionsUsing(null);
    }
}
