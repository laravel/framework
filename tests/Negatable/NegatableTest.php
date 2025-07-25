<?php

namespace Illuminate\Tests\Negatable;

use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Negatable\HigherOrderNotProxy;
use PHPUnit\Framework\TestCase;

class NegatableTest extends TestCase
{
    protected function setUp(): void
    {
        $db = new DB;

        $db->addConnection([
            'driver' => 'sqlite',
            'database' => ':memory:',
        ]);

        $db->bootEloquent();
        $db->setAsGlobal();
    }

    public function testNotInstanceOf()
    {
        $model = new TestNegatableModel;

        $this->assertInstanceOf(HigherOrderNotProxy::class, $model->not());
    }

    public function testNotWithModelMethods()
    {
        $model = new TestNegatableModel;

        $this->assertTrue($model->not()->relationLoaded('foo'));

        $model->load('foo');

        $this->assertFalse($model->not()->relationLoaded('foo'));
    }

    public function testNotProperty()
    {
        $model = new TestNegatableModel;

        $this->assertFalse($model->not()->is_active);
    }
}

class TestNegatableModel extends Model
{
    public bool $is_active = true;

    public function foo(): BelongsTo
    {
        return $this->belongsTo(Foo::class);
    }
}

class Foo extends Model {}
