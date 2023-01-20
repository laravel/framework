<?php

namespace Illuminate\Tests\Support;

use Illuminate\Support\Lottery;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class LotteryTest extends TestCase
{
    protected function tearDown(): void
    {
        parent::tearDown();

        Lottery::determineResultNormally();
    }

    public function testItCanWin()
    {
        $wins = false;

        Lottery::odds(1, 1)
            ->winner(function () use (&$wins) {
                $wins = true;
            })->choose();

        $this->assertTrue($wins);
    }

    public function testItCanLose()
    {
        $wins = false;
        $loses = false;

        Lottery::odds(0, 1)
            ->winner(function () use (&$wins) {
                $wins = true;
            })->loser(function () use (&$loses) {
                $loses = true;
            })->choose();

        $this->assertFalse($wins);
        $this->assertTrue($loses);
    }

    public function testItCanReturnValues()
    {
        $win = Lottery::odds(1, 1)->winner(fn () => 'win')->choose();
        $this->assertSame('win', $win);

        $lose = Lottery::odds(0, 1)->loser(fn () => 'lose')->choose();
        $this->assertSame('lose', $lose);
    }

    public function testItCanChooseSeveralTimes()
    {
        $results = Lottery::odds(1, 1)->winner(fn () => 'win')->choose(2);
        $this->assertSame(['win', 'win'], $results);

        $results = Lottery::odds(0, 1)->loser(fn () => 'lose')->choose(2);
        $this->assertSame(['lose', 'lose'], $results);
    }

    public function testItCanBePassedAsCallable()
    {
        // Example...
        // DB::whenQueryingForLongerThan(Interval::seconds(5), Lottery::odds(1, 5)->winner(function ($connection) {
        //     Alert the team
        // }));
        $result = (function (callable $callable) {
            return $callable('winner-chicken', '-dinner');
        })(Lottery::odds(1, 1)->winner(fn ($first, $second) => 'winner-'.$first.$second));

        $this->assertSame('winner-winner-chicken-dinner', $result);
    }

    public function testWithoutSpecifiedClosuresBooleansAreReturned()
    {
        $win = Lottery::odds(1, 1)->choose();
        $this->assertTrue($win);

        $lose = Lottery::odds(0, 1)->choose();
        $this->assertFalse($lose);
    }

    public function testItCanForceWinningResultInTests()
    {
        $result = null;
        Lottery::alwaysWin(function () use (&$result) {
            $result = Lottery::odds(1, 2)->winner(fn () => 'winner')->choose(10);
        });

        $this->assertSame([
            'winner', 'winner', 'winner', 'winner', 'winner',
            'winner', 'winner', 'winner', 'winner', 'winner',
        ], $result);
    }

    public function testItCanForceLosingResultInTests()
    {
        $result = null;
        Lottery::alwaysLose(function () use (&$result) {
            $result = Lottery::odds(1, 2)->loser(fn () => 'loser')->choose(10);
        });

        $this->assertSame([
            'loser', 'loser', 'loser', 'loser', 'loser',
            'loser', 'loser', 'loser', 'loser', 'loser',
        ], $result);
    }

    public function testItCanForceTheResultViaSequence()
    {
        $result = null;
        Lottery::forceResultWithSequence([
            true, false, true, false, true,
            false, true, false, true, false,
        ]);

        $result = Lottery::odds(1, 100)->winner(fn () => 'winner')->loser(fn () => 'loser')->choose(10);

        $this->assertSame([
            'winner', 'loser', 'winner', 'loser', 'winner',
            'loser', 'winner', 'loser', 'winner', 'loser',
        ], $result);
    }

    public function testItCanHandleMissingSequenceItems()
    {
        $result = null;
        Lottery::forceResultWithSequence([
            0 => true,
            1 => true,
            // 2 => ...
            3 => true,
        ], fn () => throw new RuntimeException('Missing key in sequence.'));

        $result = Lottery::odds(1, 10000)->winner(fn () => 'winner')->loser(fn () => 'loser')->choose();
        $this->assertSame('winner', $result);

        $result = Lottery::odds(1, 10000)->winner(fn () => 'winner')->loser(fn () => 'loser')->choose();
        $this->assertSame('winner', $result);

        $this->expectException(RuntimeException::class);
        $this->expectErrorMessage('Missing key in sequence.');
        Lottery::odds(1, 10000)->winner(fn () => 'winner')->loser(fn () => 'loser')->choose();
    }

    public function testItThrowsForFloatsOverOne()
    {
        $this->expectException(RuntimeException::class);
        $this->expectErrorMessage('Float must not be greater than 1.');

        new Lottery(1.1);
    }

    public function testItCanWinWithFloat()
    {
        $wins = false;

        Lottery::odds(1.0)
            ->winner(function () use (&$wins) {
                $wins = true;
            })->choose();

        $this->assertTrue($wins);
    }

    public function testItCanLoseWithFloat()
    {
        $wins = false;
        $loses = false;

        Lottery::odds(0.0)
            ->winner(function () use (&$wins) {
                $wins = true;
            })->loser(function () use (&$loses) {
                $loses = true;
            })->choose();

        $this->assertFalse($wins);
        $this->assertTrue($loses);
    }

    public function testItCanSetFloatProperly()
    {
        $lottery1 = new FakeLottery(5.8, 10);
        $lottery2 = new FakeLottery(3.88, 9);
        $lottery3 = new FakeLottery(4.100, 10);

        $this->assertEquals(58, $lottery1->getChances());
        $this->assertEquals(100, $lottery1->getOutOf());
        $this->assertEquals(388, $lottery2->getChances());
        $this->assertEquals(900, $lottery2->getOutOf());
        $this->assertEquals(41, $lottery3->getChances());
        $this->assertEquals(100, $lottery3->getOutOf());
    }
}

class FakeLottery extends Lottery
{

    public function getChances()
    {
       return $this->chances;
    }

    public function getOutOf()
    {
        return $this->outOf;
    }
}
