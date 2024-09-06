<?php

namespace Illuminate\Tests\Conditionable;

use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\HigherOrderWhenProxy;
use Illuminate\Support\Traits\Conditionable;
use PHPUnit\Framework\TestCase;

class ConditionableTest extends TestCase
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

    public function testWhen(): void
    {
        $this->assertInstanceOf(HigherOrderWhenProxy::class, TestConditionableModel::query()->when(true));
        $this->assertInstanceOf(HigherOrderWhenProxy::class, TestConditionableModel::query()->when(false));
        $this->assertInstanceOf(HigherOrderWhenProxy::class, TestConditionableModel::query()->when());
        $this->assertInstanceOf(Builder::class, TestConditionableModel::query()->when(false, null));
        $this->assertInstanceOf(Builder::class, TestConditionableModel::query()->when(true, function () {
        }));
    }

    public function testUnless(): void
    {
        $this->assertInstanceOf(HigherOrderWhenProxy::class, TestConditionableModel::query()->unless(true));
        $this->assertInstanceOf(HigherOrderWhenProxy::class, TestConditionableModel::query()->unless(false));
        $this->assertInstanceOf(HigherOrderWhenProxy::class, TestConditionableModel::query()->unless());
        $this->assertInstanceOf(Builder::class, TestConditionableModel::query()->unless(true, null));
        $this->assertInstanceOf(Builder::class, TestConditionableModel::query()->unless(false, function () {
        }));
    }

    public function testMatch(): void
    {
        $model = new TestConditionableModel([
            'name' => 'foo',
        ]);

        $result = $model->match(
            fn ($model) => $model->name,
            [
                'foo' => function ($model) {
                    return 'matched foo';
                },
                'bar' => function ($model) {
                    return 'matched bar';
                },
            ]
        );

        $this->assertEquals('matched foo', $result);

        $result = $model->match('foo', [
            'foo' => function ($model) {
                return 'matched foo';
            },
            'bar' => function ($model) {
                return 'matched bar';
            },
        ]);

        $this->assertEquals('matched foo', $result);

        $result = $model->match('bar', [
            'foo' => function ($model) {
                return 'matched foo';
            },
            'bar' => function ($model) {
                return 'matched bar';
            },
        ]);

        $this->assertEquals('matched bar', $result);

        $result = $model->match('baz', [
            'foo' => function ($model) {
                return 'matched foo';
            },
            'bar' => function ($model) {
                return 'matched bar';
            },
        ]);

        $this->assertInstanceOf(TestConditionableModel::class, $result);
    }
}

class TestConditionableModel extends Model
{
    use Conditionable;

    protected $fillable = ['name'];
}
