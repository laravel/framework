<?php

namespace Illuminate\Tests\Console\Scheduling;

use PHPUnit\Framework\TestCase;
use Illuminate\Support\Collection;
use Illuminate\Console\Scheduling\Schedulable;

class SchedulableTraitTest extends TestCase
{
    public $schedulableClass;

    public function setUp()
    {
        parent::setUp();

        $this->schedulableClass = new BaseSchedulableClassStub();
    }

    public function testAddsRequiredMethods()
    {
        $this->assertTrue(method_exists($this->schedulableClass, 'isDue'));

        $this->assertTrue(method_exists($this->schedulableClass, 'areDue'));

        $this->assertTrue(method_exists($this->schedulableClass, 'runSchedule'));
    }

    public function testScheduleCanBeSet()
    {
        $this->schedulableClass->everyFiveMinutes();

        $this->assertEquals('*/5 * * * *', $this->schedulableClass->expression);
    }

    public function testAreDueWithCollectionReturnsCollection()
    {
        $schedulableCollectionClass = new SchedulableCollectionClassStub();

        $this->assertTrue($schedulableCollectionClass::areDue() instanceof Collection);

        $this->assertCount(3, $schedulableCollectionClass::areDue());
    }
}

class BaseSchedulableClassStub
{
    use Schedulable;
}

class SchedulableCollectionClassStub
{
    use Schedulable;

    public static function areDue()
    {
        return collect([
            new self(),
            new self(),
            new self(),
        ]);
    }
}
