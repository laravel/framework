<?php

namespace Illuminate\Tests\Integration\Database;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * @group integration
 */
class EloquentModelTest extends DatabaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('test_model1', function (Blueprint $table) {
            $table->increments('id');
            $table->timestamp('nullable_date')->nullable();
        });

        Schema::create('test_model2', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->string('title')->nullable();
            $table->integer('score')->default(0);
            $table->json('items')->nullable();
        });
    }

    public function testUserCanUpdateNullableDate()
    {
        $user = TestModel1::create([
            'nullable_date' => null,
        ]);

        $user->fill([
            'nullable_date' => $now = Carbon::now(),
        ]);
        $this->assertTrue($user->isDirty('nullable_date'));

        $user->save();
        $this->assertEquals($now->toDateString(), $user->nullable_date->toDateString());
    }

    public function testAttributeChanges()
    {
        $user = TestModel2::create([
            'name' => Str::random(), 'title' => Str::random(),
        ]);

        $this->assertEmpty($user->getDirty());
        $this->assertEmpty($user->getChanges());
        $this->assertFalse($user->isDirty());
        $this->assertFalse($user->wasChanged());

        $user->name = $name = Str::random();

        $this->assertEquals(['name' => $name], $user->getDirty());
        $this->assertEmpty($user->getChanges());
        $this->assertTrue($user->isDirty());
        $this->assertFalse($user->wasChanged());

        $user->save();

        $this->assertEmpty($user->getDirty());
        $this->assertEquals(['name' => $name], $user->getChanges());
        $this->assertTrue($user->wasChanged());
        $this->assertTrue($user->wasChanged('name'));
    }

    public function testAttributeTransitionedFromTo()
    {
        $user = TestModel2::create([
            'name' => 'mohamed',
            'items' => [0, 1],
        ]);

        $user->refresh();

        $user->name = 'zain';
        $user->title = 'A';

        $user->save();

        $this->assertTrue($user->changedTo('name', 'zain'));
        $this->assertTrue($user->changedTo('name', 'zain', 'mohamed'));
        $this->assertTrue($user->changedTo('name', 'zain', ['mohamed', 'said']));
        $this->assertTrue($user->changedTo('name', ['zain', 'lara'], ['mohamed', 'said']));
        $this->assertTrue($user->changedTo('name', ['zain', 'lara'], 'mohamed'));
        $this->assertFalse($user->changedTo('name', 'zain', 'said'));

        $this->assertTrue($user->changedTo('title', 'A'));
        $this->assertTrue($user->changedTo('title', 'A', null));
        $this->assertTrue($user->changedTo('title', ['A', 'C'], ['BB', null]));
        $this->assertFalse($user->changedTo('title', 'A', 'B'));

        $user->title = null;

        $user->save();

        $this->assertTrue($user->changedTo('title', null));
        $this->assertTrue($user->changedTo('title', null, 'A'));
        $this->assertFalse($user->changedTo('title', null, 'B'));

        $user->title = 'B';

        $user->save();

        $this->assertTrue($user->changedTo('title', 'B'));
        $this->assertTrue($user->changedTo('title', 'B', null));
        $this->assertFalse($user->changedTo('title', 'B', 'A'));

        $user->increment('score');

        $this->assertTrue($user->changedTo('score', 1));
        $this->assertTrue($user->changedTo('score', 1, 0));
        $this->assertFalse($user->changedTo('score', 1, 2));

        $user->items = [1, 2];

        $user->save();

        $this->assertTrue($user->changedTo('items', json_encode([1, 2])));
        $this->assertTrue($user->changedTo('items', json_encode([1, 2]), json_encode([0, 1])));
    }

    public function testAttributeTransitioningFromTo()
    {
        $user = TestModel2::create([
            'name' => 'mohamed',
            'items' => [0, 1],
        ]);

        $user->refresh();

        $user->name = 'zain';
        $user->title = 'A';

        $this->assertTrue($user->changingTo('name', 'zain'));
        $this->assertTrue($user->changingTo('name', 'zain', 'mohamed'));
        $this->assertTrue($user->changingTo('name', 'zain', ['mohamed', 'said']));
        $this->assertFalse($user->changingTo('name', 'zain', 'said'));

        $this->assertTrue($user->changingTo('title', 'A'));
        $this->assertTrue($user->changingTo('title', 'A', null));
        $this->assertTrue($user->changingTo('title', ['A', 'C'], ['BB', null]));
        $this->assertFalse($user->changingTo('title', 'A', 'B'));

        $user->save();

        $user->title = null;

        $this->assertTrue($user->changingTo('title', null));
        $this->assertTrue($user->changingTo('title', null, 'A'));
        $this->assertFalse($user->changingTo('title', null, 'B'));

        $user->save();

        $user->title = 'B';

        $this->assertTrue($user->changingTo('title', 'B'));
        $this->assertTrue($user->changingTo('title', 'B', null));
        $this->assertFalse($user->changingTo('title', 'B', 'A'));

        $user->score = 1;

        $this->assertTrue($user->changingTo('score', 1));
        $this->assertTrue($user->changingTo('score', 1, 0));
        $this->assertFalse($user->changingTo('score', 1, 2));

        $user->items = [1, 2];

        $this->assertTrue($user->changingTo('items', json_encode([1, 2])));
        $this->assertTrue($user->changingTo('items', json_encode([1, 2]), json_encode([0, 1])));
    }

    public function testWormhole()
    {
        $user = TestModel2::create([
            'name' => 'mohamed',
            'title' => 'B',
            'score' => 1,
            'items' => [0, 1],
        ]);

        $user->refresh();

        $user->name = 'zain';
        $user->title = 'A';
        $user->score = 20;
        $user->items = [2, 3];

        $user->save();

        $previous = $user->wormhole();

        $this->assertEquals('mohamed', $previous->name);
        $this->assertEquals('B', $previous->title);
        $this->assertEquals(1, $previous->score);
        $this->assertEquals([0, 1], $previous->items);
    }
}

class TestModel1 extends Model
{
    public $table = 'test_model1';
    public $timestamps = false;
    protected $guarded = [];
    protected $casts = ['nullable_date' => 'datetime'];
}

class TestModel2 extends Model
{
    public $table = 'test_model2';
    public $timestamps = false;
    protected $guarded = [];
    protected $casts = [
        'score' => 'integer',
        'items' => 'array',
    ];
}
