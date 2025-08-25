<?php

namespace Illuminate\Tests\Support;

use BadMethodCallException;
use Carbon\Carbon as BaseCarbon;
use Carbon\CarbonImmutable as BaseCarbonImmutable;
use DateTimeInterface;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\TestCase;

class SupportCarbonTest extends TestCase
{
    /**
     * @var \Illuminate\Support\Carbon
     */
    protected $now;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow($this->now = Carbon::create(2017, 6, 27, 13, 14, 15, 'UTC'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow(null);
        Carbon::serializeUsing(null);

        parent::tearDown();
    }

    public function testInstance()
    {
        $this->assertInstanceOf(Carbon::class, $this->now);
        $this->assertInstanceOf(DateTimeInterface::class, $this->now);
        $this->assertInstanceOf(BaseCarbon::class, $this->now);
        $this->assertInstanceOf(Carbon::class, $this->now);
    }

    public function testCarbonIsMacroableWhenNotCalledStatically()
    {
        Carbon::macro('diffInDecades', function (?Carbon $dt = null, $abs = true) {
            return (int) ($this->diffInYears($dt, $abs) / 10);
        });

        $this->assertSame(2, $this->now->diffInDecades(Carbon::now()->addYears(25)));
    }

    public function testCarbonIsMacroableWhenCalledStatically()
    {
        Carbon::macro('twoDaysAgoAtNoon', function () {
            return Carbon::now()->subDays(2)->setTime(12, 0, 0);
        });

        $this->assertSame('2017-06-25 12:00:00', Carbon::twoDaysAgoAtNoon()->toDateTimeString());
    }

    public function testCarbonRaisesExceptionWhenStaticMacroIsNotFound()
    {
        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionMessage('nonExistingStaticMacro does not exist.');

        Carbon::nonExistingStaticMacro();
    }

    public function testCarbonRaisesExceptionWhenMacroIsNotFound()
    {
        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionMessage('nonExistingMacro does not exist.');

        Carbon::now()->nonExistingMacro();
    }

    public function testCarbonAllowsCustomSerializer()
    {
        Carbon::serializeUsing(function (Carbon $carbon) {
            return $carbon->getTimestamp();
        });

        $result = json_decode(json_encode($this->now), true);

        $this->assertSame(1498569255, $result);
    }

    public function testCarbonCanSerializeToJson()
    {
        $this->assertSame('2017-06-27T13:14:15.000000Z', $this->now->jsonSerialize());
    }

    public function testSetStateReturnsCorrectType()
    {
        $carbon = Carbon::__set_state([
            'date' => '2017-06-27 13:14:15.000000',
            'timezone_type' => 3,
            'timezone' => 'UTC',
        ]);

        $this->assertInstanceOf(Carbon::class, $carbon);
    }

    public function testDeserializationOccursCorrectly()
    {
        $carbon = new Carbon('2017-06-27 13:14:15.000000');
        $serialized = 'return '.var_export($carbon, true).';';
        $deserialized = eval($serialized);

        $this->assertInstanceOf(Carbon::class, $deserialized);
    }

    public function testSetTestNowWillPersistBetweenImmutableAndMutableInstance()
    {
        Carbon::setTestNow(new Carbon('2017-06-27 13:14:15.000000'));

        $this->assertSame('2017-06-27 13:14:15', Carbon::now()->toDateTimeString());
        $this->assertSame('2017-06-27 13:14:15', BaseCarbon::now()->toDateTimeString());
        $this->assertSame('2017-06-27 13:14:15', BaseCarbonImmutable::now()->toDateTimeString());
    }

    public function testCarbonIsConditionable()
    {
        $this->assertTrue(Carbon::now()->when(null, fn (Carbon $carbon) => $carbon->addDays(1))->isToday());
        $this->assertTrue(Carbon::now()->when(true, fn (Carbon $carbon) => $carbon->addDays(1))->isTomorrow());
    }

    public function testCreateFromUid()
    {
        $ulid = Carbon::createFromId('01DXH9C4P0ED4AGJJP9CRKQ55C');
        $this->assertEquals('2020-01-01 19:30:00.000000', $ulid->toDateTimeString('microsecond'));

        $uuidv1 = Carbon::createFromId('71513cb4-f071-11ed-a0cf-325096b39f47');
        $this->assertEquals('2023-05-12 03:02:34.147346', $uuidv1->toDateTimeString('microsecond'));

        $uuidv2 = Carbon::createFromId('000003e8-f072-21ed-9200-325096b39f47');
        $this->assertEquals('2023-05-12 03:06:33.529139', $uuidv2->toDateTimeString('microsecond'));

        $uuidv6 = Carbon::createFromId('1edf0746-5d1c-6ce8-88ad-e0cb4effa035');
        $this->assertEquals('2023-05-12 03:23:43.347428', $uuidv6->toDateTimeString('microsecond'));

        $uuidv7 = Carbon::createFromId('01880dfa-2825-72e4-acbb-b1e4981cf8af');
        $this->assertEquals('2023-05-12 03:21:18.117000', $uuidv7->toDateTimeString('microsecond'));
    }
}
