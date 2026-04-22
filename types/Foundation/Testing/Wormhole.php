<?php

use Illuminate\Foundation\Testing\Wormhole;

use function PHPStan\Testing\assertType;

$wormhole = new Wormhole(1);

$voidFunction = function () {
};

assertType('42', $wormhole->seconds(fn () => 42));
assertType('42', $wormhole->second(fn () => 42));
assertType('42', $wormhole->minutes(fn () => 42));
assertType('42', $wormhole->minute(fn () => 42));
assertType('42', $wormhole->hours(fn () => 42));
assertType('42', $wormhole->hour(fn () => 42));
assertType('42', $wormhole->days(fn () => 42));
assertType('42', $wormhole->day(fn () => 42));
assertType('42', $wormhole->weeks(fn () => 42));
assertType('42', $wormhole->week(fn () => 42));
assertType('42', $wormhole->months(fn () => 42));
assertType('42', $wormhole->month(fn () => 42));
assertType('42', $wormhole->years(fn () => 42));
assertType('42', $wormhole->year(fn () => 42));
assertType('42', $wormhole->microseconds(fn () => 42));
assertType('42', $wormhole->microsecond(fn () => 42));
assertType('42', $wormhole->milliseconds(fn () => 42));
assertType('42', $wormhole->millisecond(fn () => 42));

/** @phpstan-ignore method.void */
assertType('null', $wormhole->seconds($voidFunction));
/** @phpstan-ignore method.void */
assertType('null', $wormhole->second($voidFunction));
/** @phpstan-ignore method.void */
assertType('null', $wormhole->minutes($voidFunction));
/** @phpstan-ignore method.void */
assertType('null', $wormhole->minute($voidFunction));
/** @phpstan-ignore method.void */
assertType('null', $wormhole->hours($voidFunction));
/** @phpstan-ignore method.void */
assertType('null', $wormhole->hour($voidFunction));
/** @phpstan-ignore method.void */
assertType('null', $wormhole->days($voidFunction));
/** @phpstan-ignore method.void */
assertType('null', $wormhole->day($voidFunction));
/** @phpstan-ignore method.void */
assertType('null', $wormhole->weeks($voidFunction));
/** @phpstan-ignore method.void */
assertType('null', $wormhole->week($voidFunction));
/** @phpstan-ignore method.void */
assertType('null', $wormhole->months($voidFunction));
/** @phpstan-ignore method.void */
assertType('null', $wormhole->month($voidFunction));
/** @phpstan-ignore method.void */
assertType('null', $wormhole->years($voidFunction));
/** @phpstan-ignore method.void */
assertType('null', $wormhole->year($voidFunction));
/** @phpstan-ignore method.void */
assertType('null', $wormhole->microseconds($voidFunction));
/** @phpstan-ignore method.void */
assertType('null', $wormhole->microsecond($voidFunction));
/** @phpstan-ignore method.void */
assertType('null', $wormhole->milliseconds($voidFunction));
/** @phpstan-ignore method.void */
assertType('null', $wormhole->millisecond($voidFunction));

/** @phpstan-ignore method.void */
assertType('null', $wormhole->seconds());
/** @phpstan-ignore method.void */
assertType('null', $wormhole->second());
/** @phpstan-ignore method.void */
assertType('null', $wormhole->minutes());
/** @phpstan-ignore method.void */
assertType('null', $wormhole->minute());
/** @phpstan-ignore method.void */
assertType('null', $wormhole->hours());
/** @phpstan-ignore method.void */
assertType('null', $wormhole->hour());
/** @phpstan-ignore method.void */
assertType('null', $wormhole->days());
/** @phpstan-ignore method.void */
assertType('null', $wormhole->day());
/** @phpstan-ignore method.void */
assertType('null', $wormhole->weeks());
/** @phpstan-ignore method.void */
assertType('null', $wormhole->week());
/** @phpstan-ignore method.void */
assertType('null', $wormhole->months());
/** @phpstan-ignore method.void */
assertType('null', $wormhole->month());
/** @phpstan-ignore method.void */
assertType('null', $wormhole->years());
/** @phpstan-ignore method.void */
assertType('null', $wormhole->year());
/** @phpstan-ignore method.void */
assertType('null', $wormhole->microseconds());
/** @phpstan-ignore method.void */
assertType('null', $wormhole->microsecond());
/** @phpstan-ignore method.void */
assertType('null', $wormhole->milliseconds());
/** @phpstan-ignore method.void */
assertType('null', $wormhole->millisecond());
