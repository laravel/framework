<?php

namespace Illuminate\Tests\Http\Client\Promises;

use GuzzleHttp\Promise\Promise;
use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Http\Client\Promises\LazyPromise;
use PHPUnit\Framework\TestCase;

class LazyPromiseTest extends TestCase
{
    private function makeLazy(mixed $resolveWith = 'value'): LazyPromise
    {
        return new LazyPromise(function () use ($resolveWith) {
            $p = new Promise();
            $p->resolve($resolveWith);
            return $p;
        });
    }

    public function test_then_before_build_returns_new_instance(): void
    {
        $lazy = $this->makeLazy();

        $chained = $lazy->then(fn ($v) => $v);

        $this->assertNotSame($lazy, $chained);
        $this->assertInstanceOf(LazyPromise::class, $chained);
    }

    public function test_otherwise_before_build_returns_new_instance(): void
    {
        $lazy = $this->makeLazy();

        $chained = $lazy->otherwise(fn ($e) => null);

        $this->assertNotSame($lazy, $chained);
        $this->assertInstanceOf(LazyPromise::class, $chained);
    }

    public function test_chained_then_calls_return_distinct_instances(): void
    {
        $lazy = $this->makeLazy();

        $p1 = $lazy->then(fn ($v) => $v);
        $p2 = $lazy->then(fn ($v) => 'one');
        $p3 = $p2->then(fn ($v) => 'two');

        $this->assertNotSame($p1, $p2);
        $this->assertNotSame($p2, $p3);
        $this->assertNotSame($lazy, $p1);
    }

    public function test_chained_promises_resolve_correct_values(): void
    {
        $lazy = $this->makeLazy(10);

        $promise1 = $lazy->then(fn ($v) => $v * 2);       // 20
        $promise2 = $promise1->then(fn ($v) => $v + 5);   // 25
        $promise3 = $promise2->then(fn ($v) => $v . '!'); // "25!"

        $this->assertSame(20, $promise1->wait());
        $this->assertSame(25, $promise2->wait());
        $this->assertSame('25!', $promise3->wait());
    }

    public function test_parallel_then_chains_on_same_lazy_are_independent(): void
    {
        $lazy = $this->makeLazy(100);

        $branchA = $lazy->then(fn ($v) => $v + 1);  // 101
        $branchB = $lazy->then(fn ($v) => $v - 1);  // 99

        $this->assertSame(101, $branchA->wait());
        $this->assertSame(99, $branchB->wait());
    }

    public function test_then_after_build_returns_non_lazy_promise(): void
    {
        $lazy = $this->makeLazy('hello');
        $lazy->buildPromise();

        $result = $lazy->then(fn ($v) => strtoupper($v));


        $this->assertNotInstanceOf(LazyPromise::class, $result);
        $this->assertInstanceOf(PromiseInterface::class, $result);
    }

    public function test_wait_on_chained_builds_parent_lazily(): void
    {
        $built = false;

        $lazy = new LazyPromise(function () use (&$built) {
            $built = true;
            $p = new Promise();
            $p->resolve('done');
            return $p;
        });

        $chained = $lazy->then(fn ($v) => $v . '!');

        $this->assertFalse($built);

        $result = $chained->wait();

        $this->assertTrue($built);
        $this->assertSame('done!', $result);
    }
}
