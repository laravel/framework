<?php

namespace Illuminate\Tests\Foundation\Testing;

use stdClass;
use PHPUnit\Framework\TestCase;
use Illuminate\Support\Collection;
use Illuminate\Pagination\Paginator;
use PHPUnit\Framework\AssertionFailedError;
use Illuminate\Foundation\Testing\TestCollection;
use Illuminate\Foundation\Testing\Concerns\InteractsWithCollections;

class TestCollectionTest extends TestCase
{
    use InteractsWithCollections;

    public function testItCanBeInstantiatedWithACollection()
    {
        $testCollection = new TestCollection(new Collection);

        $this->assertInstanceOf(TestCollection::class, $testCollection);
    }

    public function testItCanBeInstantiatedWithAPaginator()
    {
        $testCollection = new TestCollection(new Paginator([], 10));

        $this->assertInstanceOf(TestCollection::class, $testCollection);
    }

    public function testFailsIfTheValueGivenIsNotACollection()
    {
        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('"stdClass Object ()" is not a collection');

        new TestCollection(new stdClass());
    }

    public function testPassesIfTheCollectionContainsTheExpectedValue()
    {
        $testCollection = new TestCollection(new Collection([1, 2, 3]));

        $testCollection->contains(2);
    }

    public function testFailsIfTheCollectionDoesNotContainTheExpectedValue()
    {
        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('The collection does not contain the expected value "5"');

        $testCollection = new TestCollection(new Collection([1, 2, 3]));

        $testCollection->contains(5);
    }

    public function testPassesIfTheCollectionDoesNotContainTheUnexpectedValue()
    {
        $testCollection = new TestCollection(new Collection([1, 2, 3]));

        $testCollection->notContains(5);
    }

    public function testFailsIfTheCollectionContainsTheUnexpectedValue()
    {
        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('The collection contains the unexpected value "2"');

        $testCollection = new TestCollection(new Collection([1, 2, 3]));

        $testCollection->notContains(2);
    }

    public function testPassesIfTheCollectionHasTheExpectedAmountOfElements()
    {
        $testCollection = new TestCollection(new Collection([1, 2, 3]));

        $testCollection->counts(3);
    }

    public function testFailsIfTheCollectionDoesNotHaveTheExpectedAmountOfElements()
    {
        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('The collection does not contain 4 elements (3 found instead)');

        $testCollection = new TestCollection(new Collection([1, 2, 3]));

        $testCollection->counts(4);
    }

    public function testItCanChainMethods()
    {
        $testCollection = new TestCollection(new Collection([1, 2, 3]));

        $testCollection->contains(2)
            ->notContains(4)
            ->counts(3);
    }

    public function testItHasACustomHelper()
    {
        $testCollection = $this->assertCollection(new Collection());

        $this->assertInstanceOf(TestCollection::class, $testCollection);
    }

    public function testFallbacksToTheParentClass()
    {
        $fallback = new class {
            public $called = false;

            public function parentMethod()
            {
                $this->called = true;
            }
        };

        $testCollection = new TestCollection(new Collection, $fallback);

        $testCollection->parentMethod();

        $this->assertTrue($fallback->called);
    }
}
