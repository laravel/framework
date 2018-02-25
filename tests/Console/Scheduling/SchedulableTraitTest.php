<?php

namespace Illuminate\Tests\Console\Scheduling;

use Mockery as m;
use PHPUnit\Framework\TestCase;
use Illuminate\Container\Container;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Console\Scheduling\Schedulable;
use Illuminate\Support\Collection;

class SchedulableTraitTest extends TestCase
{
    // // /*
    // //  * @var \Illuminate\Console\Scheduling\Schedule
    // //  */
    // public $schedule;

    public $schedulableClass;

    public function setUp()
    {
        parent::setUp();

        // $container = Container::getInstance();

        // $container->instance('Illuminate\Console\Scheduling\EventMutex', m::mock('Illuminate\Console\Scheduling\CacheEventMutex'));

        // $container->instance('Illuminate\Console\Scheduling\SchedulingMutex', m::mock('Illuminate\Console\Scheduling\CacheSchedulingMutex'));

        // $container->instance(
        //     'Illuminate\Console\Scheduling\Schedule', $this->schedule = new Schedule(m::mock('Illuminate\Console\Scheduling\EventMutex'))
        // );

        $this->schedulableClass = new FooSchedulableClassStub();
    }

    //
    //test when triat applied
        //has isDue
        //is Due is applied correctly
        //areDue returns correct collection for coll /array / object

        //runSchedule() - is applied


    //test can call a class with the Schedulable Trait
    public function testAddsRequiredMethods(){

        $this->assertTrue(method_exists($this->schedulableClass,'isDue'));

        $this->assertTrue(method_exists($this->schedulableClass,'areDue'));

        $this->assertTrue(method_exists($this->schedulableClass,'runSchedule'));
    }

    //testScheduleCnaBEUpdayted
    public function testScheduleCanBeSet(){
        $this->schedulableClass->everyFiveMinutes();

        $this->assertEquals('*/5 * * * *', $this->schedulableClass->expression);
    }

    public function testAreDueWithCollectionReturnsCollection(){
        $schedulableCollectionClass = new FooSchedulableCollectionClassStub();

        $this->assertTrue( $schedulableCollectionClass::areDue() instanceof Collection);

        $this->assertCount(3,$schedulableCollectionClass::areDue());
    }

}

class FooSchedulableClassStub{
    use Schedulable;

    public $name;
}

class FooSchedulableCollectionClassStub{
    use Schedulable;

    public $name;

    public static function areDue(){
        return collect([
            new FooSchedulableCollectionClassStub(),
            new FooSchedulableCollectionClassStub(),
            new FooSchedulableCollectionClassStub()
        ]);
    }
}
