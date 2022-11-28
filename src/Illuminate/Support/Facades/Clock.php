<?php

namespace Illuminate\Support\Facades;

/**
 * @method static \DateTimeImmutable now()
 * @method static \DateTimeImmutable withTimezone(\DateTimeZone $timezone)
 * @method static \DateTimeImmutable createFromFormat(string $format, string $datetime, ?\DateTimeZone $timezone = null)
 *
 * @see \Illuminate\Support\Clock
 */
class Clock extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'clock';
    }
}
