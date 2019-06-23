<?php

namespace Illuminate\Tests\Foundation;

use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\ExpectationFailedException;
use Illuminate\Foundation\Testing\Concerns\InteractsWithCollections;

class FoundationInteractsWithCollectionsTest extends TestCase
{
    use InteractsWithCollections;

    /**
     * @var Collection
     */
    protected $collection;

    protected function setUp(): void
    {
        $this->collection = collect([1, 2, 3]);
    }

    public function testSeeInCollectionFindsResults()
    {
        $this->assertCollectionHas($this->collection, 3);
    }

    public function testSeeInCollectionDoesNotFindResults()
    {
        $this->expectException(ExpectationFailedException::class);

        $this->assertCollectionHas($this->collection, 4);
    }

    public function testDontSeeInCollectionDoesNotFindResults()
    {
        $this->assertCollectionMissing($this->collection, 4);
    }

    public function testDontSeeInCollectionFindsResults()
    {
        $this->expectException(ExpectationFailedException::class);

        $this->assertCollectionMissing($this->collection, 3);
    }

    public function testSeeInCollectionStrictFindsResults()
    {
        $this->assertCollectionHasStrict($this->collection, 3);
    }

    public function testSeeInCollectionStrictDoesNotFindResults()
    {
        $this->expectException(ExpectationFailedException::class);

        $this->assertCollectionHasStrict($this->collection, '3');
    }

    public function testDontSeeInCollectionStrictDoesNotFindResults()
    {
        $this->assertCollectionMissingStrict($this->collection, '3');
    }

    public function testDontSeeInCollectionStrictFindsResults()
    {
        $this->expectException(ExpectationFailedException::class);

        $this->assertCollectionMissingStrict($this->collection, 3);
    }
}
