<?php

namespace Illuminate\Tests\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Enumerable;
use Illuminate\Support\LazyCollection;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class SupportEnumerableContractTest extends TestCase
{
    /**
     * Methods that are implemented by both Collection and LazyCollection
     * and therefore are expected to be part of the Enumerable contract.
     */
    public static function sharedMethodsProvider(): array
    {
        return [
            ['collapseWithKeys'],
            ['doesntContainStrict'],
            ['dot'],
            ['multiply'],
            ['select'],
        ];
    }

    #[DataProvider('sharedMethodsProvider')]
    public function testEnumerableContractDeclaresSharedMethod(string $method)
    {
        $this->assertTrue(
            method_exists(Enumerable::class, $method),
            "Failed asserting that the Enumerable contract declares [{$method}]."
        );
    }

    #[DataProvider('sharedMethodsProvider')]
    public function testImplementorsProvideSharedMethod(string $method)
    {
        $this->assertTrue(method_exists(Collection::class, $method));
        $this->assertTrue(method_exists(LazyCollection::class, $method));
    }

    public function testCollectionImplementsEveryEnumerableMethod()
    {
        $this->assertImplementsContract(Collection::class);
    }

    public function testLazyCollectionImplementsEveryEnumerableMethod()
    {
        $this->assertImplementsContract(LazyCollection::class);
    }

    protected function assertImplementsContract(string $class)
    {
        $missing = [];

        foreach ((new ReflectionClass(Enumerable::class))->getMethods() as $method) {
            if (! method_exists($class, $method->getName())) {
                $missing[] = $method->getName();
            }
        }

        $this->assertSame(
            [],
            $missing,
            "[{$class}] is missing Enumerable contract methods: ".implode(', ', $missing)
        );
    }
}
