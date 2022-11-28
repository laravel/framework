<?php

namespace Illuminate\Support;

use DateTimeImmutable;
use DateTimeZone;
use Psr\Clock\ClockInterface;

class Clock implements ClockInterface
{
    /**
     * Returns the current time as a DateTimeImmutable Object.
     *
     * @return \DateTimeImmutable
     */
    public function now(): DateTimeImmutable
    {
        return new DateTimeImmutable();
    }

    /**
     * Returns the current time with the given timezone as a DateTimeImmutable Object.
     *
     * @param  \DateTimeZone  $timezone
     * @return \DateTimeImmutable
     *
     * @throws \Exception
     */
    public function withTimezone(DateTimeZone $timezone): DateTimeImmutable
    {
        return new DateTimeImmutable('now', $timezone);
    }

    /**
     * Creates a DateTimeImmutable Object based in the given format, datetime and timezone.
     *
     * @param  string  $format
     * @param  string  $datetime
     * @param  \DateTimeZone|null  $timezone
     * @return \DateTimeImmutable|false
     */
    public function createFromFormat(string $format, string $datetime, ?DateTimeZone $timezone = null)
    {
        return DateTimeImmutable::createFromFormat($format, $datetime, $timezone);
    }
}
