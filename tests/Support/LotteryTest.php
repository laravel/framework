<?php

namespace Illuminate\Tests\Support;

use Illuminate\Support\Lottery;
use PHPUnit\Framework\TestCase;

class LotteryTest extends TestCase
{
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
        // Exmaple...
        // DB::whenQueryingForLongerThan(Interval::seconds(5), Lottery::odds(1, 5)->winner(function ($connection) {
            // Alert the team
        // }))2;
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
}
