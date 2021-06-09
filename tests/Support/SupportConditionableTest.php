<?php

namespace Illuminate\Tests\Support;

use Exception;
use Illuminate\Support\Traits\Conditionable;
use PHPUnit\Framework\TestCase;

class SupportConditionableTest extends TestCase
{
    public function testWhenConditionCallback()
    {
        $object = (new CustomConditionableObject())
            ->when(2, function ($object, $condition) {
                $object->on();
                $this->assertEquals(2, $condition);
            }, function () {
                throw new Exception('when() should not trigger default callback on a truthy value');
            });

        $this->assertTrue($object->enabled);
    }

    public function testWhenDefaultCallback()
    {
        $object = (new CustomConditionableObject())
            ->when(null, function () {
                throw new Exception('when() should not trigger on a falsy value');
            }, function ($object, $condition) {
                $object->on();
                $this->assertNull($condition);
            });

        $this->assertTrue($object->enabled);
    }

    public function testUnlessConditionCallback()
    {
        $object = (new CustomConditionableObject())
            ->unless(null, function ($object, $condition) {
                $object->on();
                $this->assertNull($condition);
            }, function () {
                throw new Exception('unless() should not trigger default callback on a falsy value');
            });

        $this->assertTrue($object->enabled);
    }

    public function testUnlessDefaultCallback()
    {
        $object = (new CustomConditionableObject())
            ->unless(2, function () {
                throw new Exception('unless() should not trigger on a truthy value');
            }, function ($object, $condition) {
                $object->on();
                $this->assertEquals(2, $condition);
            });

        $this->assertTrue($object->enabled);
    }

    public function testWhenProxy()
    {
        $object = (new CustomConditionableObject())->when(true)->on();

        $this->assertInstanceOf(CustomConditionableObject::class, $object);
        $this->assertTrue($object->enabled);

        $object = (new CustomConditionableObject())->when(false)->on();

        $this->assertInstanceOf(CustomConditionableObject::class, $object);
        $this->assertFalse($object->enabled);
    }

    public function testUnlessProxy()
    {
        $object = (new CustomConditionableObject())->unless(false)->on();

        $this->assertInstanceOf(CustomConditionableObject::class, $object);
        $this->assertTrue($object->enabled);

        $object = (new CustomConditionableObject())->unless(true)->on();

        $this->assertInstanceOf(CustomConditionableObject::class, $object);
        $this->assertFalse($object->enabled);
    }
}

class CustomConditionableObject
{
    use Conditionable;

    public $enabled = false;

    public function on()
    {
        $this->enabled = true;

        return $this;
    }

    public function off()
    {
        $this->enabled = false;

        return $this;
    }
}
