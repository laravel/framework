<?php

namespace Illuminate\Tests\Support;

use Faker\Factory;
use Illuminate\Support\Faker;
use PHPUnit\Framework\TestCase;

class SupportFakerTest extends TestCase
{
    public function testIsAChildOfFakerFactory()
    {
        $faker = faker();

        $this->assertInstanceOf(Factory::class, $faker);
    }
    
    public function testAFakerHelperIsGloballyAvailable()
    {
        $faker = faker();

        $this->assertInstanceOf(Faker::class, $faker);
    }

    public function testCanAcceptACount()
    {
        $faker = faker(10);

        $this->assertCount(10, $faker);
    }

    public function testCollectionContainsFakerInstances()
    {
        $faker = faker(10);

        $this->assertInstanceOf(Faker::class, $faker->first());
    }

    public function testCanReturnARandomName()
    {
        $name = faker()->name();

        $this->assertIsString($name);
    }

    public function testCanReturnARandomNameWhenCreatedAsACollection()
    {
        $faker = faker(10);

        $this->assertIsString($faker->first()->name());
    }
}
