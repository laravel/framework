<?php

namespace Illuminate\Console\Scheduling;

use DateTimeZone;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class CronExpressionTimezoneConverter
{
    /**
     * Convert an event cron expression to the display timezone.
     *
     * Returns one or more expressions when values straddle a day boundary.
     *
     * @param  \Illuminate\Console\Scheduling\Event  $event
     * @param  \DateTimeZone  $timezone
     * @return array<string>
     */
    public static function forEvent(Event $event, DateTimeZone $timezone)
    {
        $eventTimezone = static::resolveEventTimezone($event, $timezone);

        [$totalOffsetMinutes, $hourOffset, $minuteOffset] = static::offsetComponents(
            $eventTimezone, $timezone
        );

        if ($totalOffsetMinutes === 0) {
            return [$event->expression];
        }

        $segments = preg_split("/\s+/", $event->expression);
        $minuteGroups = static::shiftAndGroup($segments[0], $minuteOffset, 60);

        $expressions = [];

        foreach ($minuteGroups as $minuteCarry => $minuteValues) {
            $hourGroups = static::shiftAndGroup($segments[1], $hourOffset + $minuteCarry, 24);

            foreach ($hourGroups as $hourCarry => $hourValues) {
                $parts = $segments;
                $parts[0] = $minuteValues;
                $parts[1] = $hourValues;

                foreach (static::expressionsForHourCarry($segments, $parts, $hourCarry) as $expression) {
                    $expressions[] = $expression;
                }
            }
        }

        return $expressions;
    }

    /**
     * Resolve the timezone used by the given event.
     *
     * @param  \Illuminate\Console\Scheduling\Event  $event
     * @param  \DateTimeZone  $defaultTimezone
     * @return \DateTimeZone
     */
    protected static function resolveEventTimezone(Event $event, DateTimeZone $defaultTimezone)
    {
        return $event->timezone instanceof DateTimeZone
            ? $event->timezone
            : new DateTimeZone($event->timezone ?? $defaultTimezone->getName());
    }

    /**
     * Get offset components between the event and display timezones.
     *
     * @return array{int, int, int}
     */
    protected static function offsetComponents(DateTimeZone $eventTimezone, DateTimeZone $displayTimezone)
    {
        $now = Carbon::now();

        $totalOffsetMinutes = intdiv(
            $displayTimezone->getOffset($now) - $eventTimezone->getOffset($now),
            60
        );

        return [$totalOffsetMinutes, intdiv($totalOffsetMinutes, 60), $totalOffsetMinutes % 60];
    }

    /**
     * Build expressions for the given hour carry direction.
     *
     * @param  array<int, string>  $segments
     * @param  array<int, string>  $parts
     * @return array<int, string>
     */
    protected static function expressionsForHourCarry(array $segments, array $parts, int $hourCarry)
    {
        if ($hourCarry === 0) {
            return [implode(' ', $parts)];
        }

        $parts[4] = static::shiftField($segments[4], $hourCarry, 7);

        $dayGroups = static::shiftAndGroup($segments[2], $hourCarry, 31, min: 1);

        $expressions = [];

        foreach ($dayGroups as $dayCarry => $dayValues) {
            $dayParts = $parts;
            $dayParts[2] = $dayValues;

            if ($dayCarry !== 0) {
                $dayParts[3] = static::shiftField($segments[3], $dayCarry, 12, min: 1);
            }

            $expressions[] = implode(' ', $dayParts);
        }

        return $expressions;
    }

    /**
     * Shift values in a cron field and group them by carry direction.
     *
     * @param  string  $field
     * @param  int  $offset
     * @param  int  $mod
     * @return array<int, string>
     */
    protected static function shiftAndGroup($field, $offset, $mod, $min = 0)
    {
        if ($offset === 0) {
            return [0 => $field];
        }

        $values = static::expandField($field, $mod, $min);

        if ($values === null) {
            return [0 => $field];
        }

        $groups = [];

        foreach ($values as $value) {
            $new = (int) $value + $offset;
            $carry = 0;

            if ($new >= $mod + $min) {
                $carry = 1;
                $new -= $mod;
            } elseif ($new < $min) {
                $carry = -1;
                $new += $mod;
            }

            $groups[$carry][] = $new;
        }

        return (new Collection($groups))->map(function ($values) {
            sort($values);

            return implode(',', $values);
        })->all();
    }

    /**
     * Expand a cron field containing stepped values.
     *
     * @param  string  $field
     * @param  int  $mod
     * @param  int  $min
     * @return array<int>|null
     */
    protected static function expandField($field, $mod, $min = 0)
    {
        if (preg_match('/^[\d,]+$/', $field)) {
            return array_map('intval', explode(',', $field));
        }

        if (! preg_match('/^(?<start>\*|\d+)(?:-(?<end>\d+))?\/(?<step>\d+)$/', $field, $matches)) {
            return null;
        }

        $start = $matches['start'] === '*' ? $min : (int) $matches['start'];
        $end = $matches['end'] !== '' ? (int) $matches['end'] : $mod + $min - 1;
        $step = (int) $matches['step'];

        if ($step === 0 || $start < $min || $end >= $mod + $min || $start > $end) {
            return null;
        }

        return range($start, $end, $step);
    }

    /**
     * Shift a cron field by the given offset.
     *
     * @param  string  $field
     * @param  int  $offset
     * @param  int  $mod
     * @param  int  $min
     * @return string
     */
    protected static function shiftField($field, $offset, $mod, $min = 0)
    {
        if ($offset === 0 || ! preg_match('/^[\d,]+$/', $field)) {
            return $field;
        }

        $shifted = (new Collection(explode(',', $field)))
            ->map(fn ($v) => (((int) $v + $offset - $min) % $mod + $mod) % $mod + $min)
            ->sort();

        return $shifted->implode(',');
    }
}
