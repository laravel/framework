<?php

namespace Illuminate\Tests\Support;

use Carbon\CarbonInterval as Duration;
use Illuminate\Foundation\Testing\Wormhole;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\LazyCollection;
use Illuminate\Support\Sleep;
use InvalidArgumentException;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class SupportLazyCollectionTest extends TestCase
{
    public function testCanCreateEmptyCollection()
    {
        $this->assertSame([], LazyCollection::make()->all());
        $this->assertSame([], LazyCollection::empty()->all());
    }

    public function testCanCreateCollectionFromArray()
    {
        $array = [1, 2, 3];

        $data = LazyCollection::make($array);

        $this->assertSame($array, $data->all());

        $array = ['a' => 1, 'b' => 2, 'c' => 3];

        $data = LazyCollection::make($array);

        $this->assertSame($array, $data->all());
    }

    public function testCanCreateCollectionFromArrayable()
    {
        $array = [1, 2, 3];

        $data = LazyCollection::make(Collection::make($array));

        $this->assertSame($array, $data->all());

        $array = ['a' => 1, 'b' => 2, 'c' => 3];

        $data = LazyCollection::make(Collection::make($array));

        $this->assertSame($array, $data->all());
    }

    public function testCanCreateCollectionFromGeneratorFunction()
    {
        $data = LazyCollection::make(function () {
            yield 1;
            yield 2;
            yield 3;
        });

        $this->assertSame([1, 2, 3], $data->all());

        $data = LazyCollection::make(function () {
            yield 'a' => 1;
            yield 'b' => 2;
            yield 'c' => 3;
        });

        $this->assertSame([
            'a' => 1,
            'b' => 2,
            'c' => 3,
        ], $data->all());
    }

    public function testCanCreateCollectionFromNonGeneratorFunction()
    {
        $data = LazyCollection::make(function () {
            return 'laravel';
        });

        $this->assertSame(['laravel'], $data->all());
    }

    public function testDoesNotCreateCollectionFromGenerator()
    {
        $this->expectException(InvalidArgumentException::class);

        $generateNumber = function () {
            yield 1;
        };

        LazyCollection::make($generateNumber());
    }

    public function testEager()
    {
        $source = [1, 2, 3, 4, 5];

        $data = LazyCollection::make(function () use (&$source) {
            yield from $source;
        })->eager();

        $source[] = 6;

        $this->assertSame([1, 2, 3, 4, 5], $data->all());
    }

    public function testRemember()
    {
        $source = [1, 2, 3, 4];

        $collection = LazyCollection::make(function () use (&$source) {
            yield from $source;
        })->remember();

        $this->assertSame([1, 2, 3, 4], $collection->all());

        $source = [];

        $this->assertSame([1, 2, 3, 4], $collection->all());
    }

    public function testRememberWithTwoRunners()
    {
        $source = [1, 2, 3, 4];

        $collection = LazyCollection::make(function () use (&$source) {
            yield from $source;
        })->remember();

        $a = $collection->getIterator();
        $b = $collection->getIterator();

        $this->assertEquals(1, $a->current());
        $this->assertEquals(1, $b->current());

        $b->next();

        $this->assertEquals(1, $a->current());
        $this->assertEquals(2, $b->current());

        $b->next();

        $this->assertEquals(1, $a->current());
        $this->assertEquals(3, $b->current());

        $a->next();

        $this->assertEquals(2, $a->current());
        $this->assertEquals(3, $b->current());

        $a->next();

        $this->assertEquals(3, $a->current());
        $this->assertEquals(3, $b->current());

        $a->next();

        $this->assertEquals(4, $a->current());
        $this->assertEquals(3, $b->current());

        $b->next();

        $this->assertEquals(4, $a->current());
        $this->assertEquals(4, $b->current());
    }

    public function testRememberWithDuplicateKeys()
    {
        $collection = LazyCollection::make(function () {
            yield 'key' => 1;
            yield 'key' => 2;
        })->remember();

        $results = $collection->map(function ($value, $key) {
            return [$key, $value];
        })->values()->all();

        $this->assertSame([['key', 1], ['key', 2]], $results);
    }

    public function testTakeUntilTimeout()
    {
        $timeout = Carbon::now();

        $mock = m::mock(LazyCollection::class.'[now]');

        $timedOutWith = [];

        $results = $mock
            ->times(10)
            ->tap(function ($collection) use ($mock, $timeout) {
                tap($collection)
                    ->mockery_init($mock->mockery_getContainer())
                    ->shouldAllowMockingProtectedMethods()
                    ->shouldReceive('now')
                    ->times(3)
                    ->andReturn(
                        (clone $timeout)->sub(2, 'minute')->getTimestamp(),
                        (clone $timeout)->sub(1, 'minute')->getTimestamp(),
                        $timeout->getTimestamp()
                    );
            })
            ->takeUntilTimeout($timeout, function ($value, $key) use (&$timedOutWith) {
                $timedOutWith = [$value, $key];
            })
            ->all();

        $this->assertSame([1, 2], $results);
        $this->assertSame([2, 1], $timedOutWith);

        m::close();
    }

    public function testTapEach()
    {
        $data = LazyCollection::times(10);

        $tapped = [];

        $data = $data->tapEach(function ($value, $key) use (&$tapped) {
            $tapped[$key] = $value;
        });

        $this->assertEmpty($tapped);

        $data = $data->take(5)->all();

        $this->assertSame([1, 2, 3, 4, 5], $data);
        $this->assertSame([1, 2, 3, 4, 5], $tapped);
    }

    public function testThrottle()
    {
        Sleep::fake();

        $data = LazyCollection::times(3)
            ->throttle(2)
            ->all();

        Sleep::assertSlept(function (Duration $duration) {
            $this->assertEqualsWithDelta(
                2_000_000, $duration->totalMicroseconds, 1_000
            );

            return true;
        }, times: 3);

        $this->assertSame([1, 2, 3], $data);

        Sleep::fake(false);
    }

    public function testThrottleAccountsForTimePassed()
    {
        Sleep::fake();
        Carbon::setTestNow(now());

        $data = LazyCollection::times(3)
            ->throttle(3)
            ->tapEach(function ($value, $index) {
                if ($index == 1) {
                    // Travel in time...
                    (new Wormhole(1))->second();
                }
            })
            ->all();

        Sleep::assertSlept(function (Duration $duration, int $index) {
            $expectation = $index == 1 ? 2_000_000 : 3_000_000;

            $this->assertEqualsWithDelta(
                $expectation, $duration->totalMicroseconds, 1_000
            );

            return true;
        }, times: 3);

        $this->assertSame([1, 2, 3], $data);

        Sleep::fake(false);
        Carbon::setTestNow();
    }

    public function testUniqueDoubleEnumeration()
    {
        $data = LazyCollection::times(2)->unique();

        $data->all();

        $this->assertSame([1, 2], $data->all());
    }

    public function testAfter()
    {
        $data = new LazyCollection([1, '2', 3, 4]);

        // Test finding item after value with non-strict comparison
        $result = $data->after(1);
        $this->assertSame('2', $result);

        // Test with strict comparison
        $result = $data->after('2', true);
        $this->assertSame(3, $result);

        $users = new LazyCollection([
            ['name' => 'Taylor', 'age' => 35],
            ['name' => 'Jeffrey', 'age' => 45],
            ['name' => 'Mohamed', 'age' => 35],
        ]);

        // Test finding item after the one that matches a condition
        $result = $users->after(function ($user) {
            return $user['name'] === 'Jeffrey';
        });

        $this->assertSame(['name' => 'Mohamed', 'age' => 35], $result);
    }

    public function testBefore()
    {
        // Test finding item before value with non-strict comparison
        $data = new LazyCollection([1, 2, '3', 4]);
        $result = $data->before(2);
        $this->assertSame(1, $result);

        // Test finding item before value with strict comparison
        $result = $data->before(4, true);
        $this->assertSame('3', $result);

        // Test finding item before the one that matches a callback condition
        $users = new LazyCollection([
            ['name' => 'Taylor', 'age' => 35],
            ['name' => 'Jeffrey', 'age' => 45],
            ['name' => 'Mohamed', 'age' => 35],
        ]);
        $result = $users->before(function ($user) {
            return $user['name'] === 'Jeffrey';
        });
        $this->assertSame(['name' => 'Taylor', 'age' => 35], $result);
    }

    public function testShuffle()
    {
        $data = new LazyCollection([1, 2, 3, 4, 5]);
        $shuffled = $data->shuffle();

        $this->assertCount(5, $shuffled);
        $this->assertEquals([1, 2, 3, 4, 5], $shuffled->sort()->values()->all());

        // Test shuffling associative array maintains key-value pairs
        $users = new LazyCollection([
            'first' => ['name' => 'Taylor'],
            'second' => ['name' => 'Jeffrey'],
        ]);
        $shuffled = $users->shuffle();

        $this->assertCount(2, $shuffled);
        $this->assertTrue($shuffled->contains('name', 'Taylor'));
        $this->assertTrue($shuffled->contains('name', 'Jeffrey'));
    }

    public function testCollapseWithKeys()
    {
        $collection = new LazyCollection([
            ['a' => 1, 'b' => 2],
            ['c' => 3, 'd' => 4],
        ]);
        $collapsed = $collection->collapseWithKeys();

        $this->assertEquals(['a' => 1, 'b' => 2, 'c' => 3, 'd' => 4], $collapsed->all());

        $collection = new LazyCollection([
            ['a' => 1],
            new LazyCollection(['b' => 2]),
        ]);
        $collapsed = $collection->collapseWithKeys();

        $this->assertEquals(['a' => 1, 'b' => 2], $collapsed->all());
    }

    public function testContainsOneItem()
    {
        $collection = new LazyCollection([5]);
        $this->assertTrue($collection->containsOneItem());

        $emptyCollection = new LazyCollection([]);
        $this->assertFalse($emptyCollection->containsOneItem());

        $multipleCollection = new LazyCollection([1, 2, 3]);
        $this->assertFalse($multipleCollection->containsOneItem());
    }

    public function testDoesntContain()
    {
        $collection = new LazyCollection([1, 2, 3, 4, 5]);

        $this->assertTrue($collection->doesntContain(10));
        $this->assertFalse($collection->doesntContain(3));
        $this->assertTrue($collection->doesntContain('value', '>', 10));
        $this->assertTrue($collection->doesntContain(function ($value) {
            return $value > 10;
        }));

        $users = new LazyCollection([
            [
                'name' => 'Taylor',
                'role' => 'developer',
            ],
            [
                'name' => 'Jeffrey',
                'role' => 'designer',
            ],
        ]);

        $this->assertTrue($users->doesntContain('name', 'Adam'));
        $this->assertFalse($users->doesntContain('name', 'Taylor'));
    }

    public function testDot()
    {
        $collection = new LazyCollection([
            'foo' => [
                'bar' => 'baz',
            ],
            'user' => [
                'name' => 'Taylor',
                'profile' => [
                    'age' => 30,
                ],
            ],
            'users' => [
                0 => [
                    'name' => 'Taylor',
                ],
                1 => [
                    'name' => 'Jeffrey',
                ],
            ],
        ]);

        $dotted = $collection->dot();

        $expected = [
            'foo.bar' => 'baz',
            'user.name' => 'Taylor',
            'user.profile.age' => 30,
            'users.0.name' => 'Taylor',
            'users.1.name' => 'Jeffrey',
        ];

        $this->assertEquals($expected, $dotted->all());
    }

    public function testWithHeartbeat()
    {
        $start = Carbon::create(2000, 1, 1);
        $after2Minutes = $start->copy()->addMinutes(2);
        $after5Minutes = $start->copy()->addMinutes(5);
        $after7Minutes = $start->copy()->addMinutes(7);
        $after11Minutes = $start->copy()->addMinutes(11);

        Carbon::setTestNow($start);

        $output = new Collection();

        $numbers = LazyCollection::range(1, 10)

            // Move the clock to possibly trigger the heartbeat...
            ->tapEach(fn ($number) => Carbon::setTestNow(
                match ($number) {
                    3 => $after2Minutes,
                    4 => $after5Minutes,
                    6 => $after7Minutes,
                    9 => $after11Minutes,
                    default => Carbon::now(),
                }
            ))

            // Push the current date to `output` when heartbeat is triggered...
            ->withHeartbeat(Duration::minutes(5), fn () => $output[] = Carbon::now())

            // Push every number onto `output` as it's enumerated...
            ->tapEach(fn ($number) => $output[] = $number)->all();

        $this->assertEquals(range(1, 10), $numbers);

        $this->assertEquals(
            [
                1, 2, 3,
                $after5Minutes,
                4, 5, 6, 7, 8,
                $after11Minutes,
                9, 10,
            ],
            $output->all(),
        );

        Carbon::setTestNow();
    }
}
