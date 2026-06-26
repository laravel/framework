<?php

namespace Illuminate\Tests\Http\Client\Promises;

use GuzzleHttp\Promise\Promise;
use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Http\Client\Promises\FluentPromise;
use PHPUnit\Framework\TestCase;

class FluentPromiseTest extends TestCase
{
    public function test_then_returns_new_instance(): void
    {
        $guzzlePromise = new Promise();
        $fluent = new FluentPromise($guzzlePromise);

        $chained = $fluent->then(fn ($v) => $v);

        $this->assertNotSame($fluent, $chained);
        $this->assertInstanceOf(FluentPromise::class, $chained);
    }

    public function test_otherwise_returns_new_instance(): void
    {
        $guzzlePromise = new Promise();
        $fluent = new FluentPromise($guzzlePromise);

        $chained = $fluent->otherwise(fn ($v) => $v);

        $this->assertNotSame($fluent, $chained);
        $this->assertInstanceOf(FluentPromise::class, $chained);
    }

    public function test_chained_then_calls_return_distinct_instances(): void
    {
        $guzzlePromise = new Promise();
        $fluent = new FluentPromise($guzzlePromise);

        $promise1 = $fluent->then(fn ($v) => $v);
        $promise2 = $fluent->then(fn ($v) => 'one');
        $promise3 = $promise2->then(fn ($v) => 'two');

        $this->assertNotSame($promise1, $promise2);
        $this->assertNotSame($promise2, $promise3);
        $this->assertNotSame($promise1, $promise3);
    }

    public function test_then_chain_resolves_correct_values(): void
    {
        $guzzlePromise = new Promise();
        $fluent = new FluentPromise($guzzlePromise);

        $result1 = null;
        $result2 = null;
        $result3 = null;

        $promise1 = $fluent->then(function ($v) use (&$result1) {
            $result1 = $v;
            return $v * 2;
        });

        $promise2 = $promise1->then(function ($v) use (&$result2) {
            $result2 = $v;
            return $v + 1;
        });

        $promise3 = $promise2->then(function ($v) use (&$result3) {
            $result3 = $v;
            return $v;
        });

        $guzzlePromise->resolve(5);
        $promise3->wait(false);

        $this->assertSame(5, $result1);   // отримав вихідне значення
        $this->assertSame(10, $result2);  // отримав 5 * 2
        $this->assertSame(11, $result3);  // отримав 10 + 1
    }

    public function test_underlying_guzzle_promise_differs_per_chain(): void
    {
        $guzzlePromise = new Promise();
        $fluent = new FluentPromise($guzzlePromise);

        $p1 = $fluent->then(fn ($v) => $v);
        $p2 = $fluent->then(fn ($v) => $v);

        $this->assertNotSame($p1->getGuzzlePromise(), $p2->getGuzzlePromise());
    }
}
