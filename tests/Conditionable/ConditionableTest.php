<?php

namespace Illuminate\Tests\Conditionable;

use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Eloquent\Model;
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
        $this->assertInstanceOf(\Illuminate\Support\HigherOrderWhenProxy::class, TestConditionableModel::query()->when(true));
        $this->assertInstanceOf(\Illuminate\Support\HigherOrderWhenProxy::class, TestConditionableModel::query()->when(false));
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Builder::class, TestConditionableModel::query()->when(false, null));
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Builder::class, TestConditionableModel::query()->when(true, function () {
        }));
    }

    public function testUnless(): void
    {
        $this->assertInstanceOf(\Illuminate\Support\HigherOrderWhenProxy::class, TestConditionableModel::query()->unless(true));
        $this->assertInstanceOf(\Illuminate\Support\HigherOrderWhenProxy::class, TestConditionableModel::query()->unless(false));
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Builder::class, TestConditionableModel::query()->unless(true, null));
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Builder::class, TestConditionableModel::query()->unless(false, function () {
        }));
    }
}

class TestConditionableModel extends Model
{
}
