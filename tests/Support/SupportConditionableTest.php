<?php

namespace Illuminate\Tests\Support;

use Illuminate\Support\Optional;
use Illuminate\Support\Traits\Conditionable;
use PHPUnit\Framework\TestCase;
use stdClass;

class SupportConditionableTest extends TestCase
{
    public function testWhenConditionCallback()
    {
        $conditionTriggered = false;
        $defaultTriggered = false;

        $object = (new CustomConditionableObject())
            ->when(2, function($object, $condition) use (&$conditionTriggered) {
                $conditionTriggered = true;
                $object->on();
                $this->assertEquals(2, $condition);
            }, function($object) use (&$defaultTriggered) {
                $defaultTriggered = true;
                $object->off();
            });

        $this->assertTrue($object->enabled);
        $this->assertTrue($conditionTriggered);
        $this->assertFalse($defaultTriggered);
    }

    public function testWhenDefaultCallback()
    {
        $conditionTriggered = false;
        $defaultTriggered = false;

        $object = (new CustomConditionableObject())
            ->when(null, function ($object) use (&$conditionTriggered) {
                $conditionTriggered = true;
                $object->on();
            }, function ($object, $condition) use (&$defaultTriggered) {
                $defaultTriggered = true;
                $object->up();
                $this->assertNull($condition);
            });

        $this->assertFalse($object->enabled);
        $this->assertEquals('up', $object->direction);
        $this->assertFalse($conditionTriggered);
        $this->assertTrue($defaultTriggered);
    }

    public function testUnlessConditionCallback()
    {
        $conditionTriggered = false;
        $defaultTriggered = false;

        $object = (new CustomConditionableObject())
            ->unless(null, function ($object, $condition) use (&$conditionTriggered) {
                $conditionTriggered = true;
                $object->on();
                $this->assertNull($condition);
            }, function ($object) use (&$defaultTriggered) {
                $defaultTriggered = true;
                $object->up();
            });

        $this->assertTrue($object->enabled);
        $this->assertEquals('down', $object->direction);
        $this->assertTrue($conditionTriggered);
        $this->assertFalse($defaultTriggered);
    }

    public function testUnlessDefaultCallback()
    {
        $conditionTriggered = false;
        $defaultTriggered = false;

        $object = (new CustomConditionableObject())
            ->unless(2, function ($object) use (&$conditionTriggered) {
                $conditionTriggered = true;
                $object->on();
            }, function ($object, $condition) use (&$defaultTriggered) {
                $defaultTriggered = true;
                $object->off();
                $this->assertEquals(2, $condition);
            });

        $this->assertFalse($object->enabled);
        $this->assertFalse($conditionTriggered);
        $this->assertTrue($defaultTriggered);
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

    public $direction = 'down';

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

    public function down()
    {
        $this->direction = 'down';

        return $this;
    }

    public function up()
    {
        $this->direction = 'up';

        return $this;
    }
}
