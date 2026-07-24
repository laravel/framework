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

        $segments = preg_split("/\s+/", trim($event->expression));

        if (count($segments) !== 5) {
            return [$event->expression];
        }

        return static::convert($segments, $hourOffset, $minuteOffset) ?? [$event->expression];
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
     * Convert the expression segments by the given offsets.
     *
     * @param  array<int, string>  $segments
     * @param  int  $hourOffset
     * @param  int  $minuteOffset
     * @return array<int, string>|null
     */
    protected static function convert(array $segments, int $hourOffset, int $minuteOffset)
    {
        $daysAreWildcard = $segments[2] === '*' && $segments[3] === '*' && $segments[4] === '*';

        // A carry out of a field is irrelevant when every field above it
        // matches everything, so mixed carry groups may merge in that case.
        $minuteGroups = static::shiftedGroups(
            $segments[0], $minuteOffset, 0, 59,
            mergeCarries: $daysAreWildcard && static::expand($segments[1], 0, 23) === range(0, 23),
        );

        if (is_null($minuteGroups)) {
            return null;
        }

        $expressions = [];

        foreach ($minuteGroups as $minuteCarry => $minuteValues) {
            $hourGroups = static::shiftedGroups(
                $segments[1], $hourOffset + $minuteCarry, 0, 23, mergeCarries: $daysAreWildcard,
            );

            if (is_null($hourGroups)) {
                return null;
            }

            foreach ($hourGroups as $hourCarry => $hourValues) {
                $parts = $segments;
                $parts[0] = $minuteValues;
                $parts[1] = $hourValues;

                if (is_null($carried = static::expressionsForHourCarry($segments, $parts, $hourCarry))) {
                    return null;
                }

                $expressions = array_merge($expressions, $carried);
            }
        }

        return $expressions;
    }

    /**
     * Build expressions for the given hour carry direction.
     *
     * @param  array<int, string>  $segments
     * @param  array<int, string>  $parts
     * @return array<int, string>|null
     */
    protected static function expressionsForHourCarry(array $segments, array $parts, int $hourCarry)
    {
        if ($hourCarry === 0) {
            return [implode(' ', $parts)];
        }

        if ($segments[4] !== '*') {
            if (is_null($days = static::expand($segments[4], 0, 7))) {
                return null;
            }

            $parts[4] = static::collapse(static::shiftWrapped($days, $hourCarry, 7), 0, 6);
        }

        $dayGroups = $segments[2] === '*'
            ? [0 => '*']
            : static::shiftedGroups($segments[2], $hourCarry, 1, 31, mergeCarries: false);

        if (is_null($dayGroups)) {
            return null;
        }

        $expressions = [];

        foreach ($dayGroups as $dayCarry => $dayValues) {
            $dayParts = $parts;
            $dayParts[2] = $dayValues;

            if ($dayCarry !== 0 && $segments[3] !== '*') {
                if (is_null($months = static::expand($segments[3], 1, 12))) {
                    return null;
                }

                $dayParts[3] = static::collapse(static::shiftWrapped($months, $dayCarry, 12, min: 1), 1, 12);
            }

            $expressions[] = implode(' ', $dayParts);
        }

        return $expressions;
    }

    /**
     * Shift a cron field by the given offset and group it by carry direction.
     *
     * @param  string  $field
     * @param  int  $offset
     * @param  int  $min
     * @param  int  $max
     * @param  bool  $mergeCarries
     * @return array<int, string>|null
     */
    protected static function shiftedGroups(string $field, int $offset, int $min, int $max, bool $mergeCarries)
    {
        if ($offset === 0) {
            return [0 => $field];
        }

        if (is_null($values = static::expand($field, $min, $max))) {
            return null;
        }

        $groups = static::shiftAndGroupValues($values, $offset, $max - $min + 1, $min);

        if ($mergeCarries && count($groups) > 1) {
            $groups = [0 => static::mergeGroups($groups)];
        }

        return array_map(fn ($group) => static::collapse($group, $min, $max), $groups);
    }

    /**
     * Expand a cron field into the sorted list of values it matches.
     *
     * @param  string  $field
     * @param  int  $min
     * @param  int  $max
     * @return array<int, int>|null
     */
    protected static function expand(string $field, int $min, int $max)
    {
        if ($field === '*') {
            return range($min, $max);
        }

        $values = [];

        foreach (explode(',', $field) as $part) {
            if (! preg_match('/^(\*|\d+(?:-\d+)?)(?:\/(\d+))?$/', $part, $matches)) {
                return null;
            }

            $step = ($matches[2] ?? '') !== '' ? (int) $matches[2] : null;

            if ($step === 0) {
                return null;
            }

            if ($matches[1] === '*') {
                [$start, $end] = [$min, $max];
            } elseif (str_contains($matches[1], '-')) {
                [$start, $end] = array_map(intval(...), explode('-', $matches[1]));
            } else {
                [$start, $end] = [(int) $matches[1], is_null($step) ? (int) $matches[1] : $max];
            }

            if ($start < $min || $end > $max || $start > $end) {
                return null;
            }

            for ($value = $start; $value <= $end; $value += $step ?? 1) {
                $values[] = $value;
            }
        }

        $values = array_values(array_unique($values));

        sort($values);

        return $values;
    }

    /**
     * Shift values in a cron field and group them by carry direction.
     *
     * @param  array<int, int>  $values
     * @param  int  $offset
     * @param  int  $mod
     * @param  int  $min
     * @return array<int, array<int, int>>
     */
    protected static function shiftAndGroupValues(array $values, int $offset, int $mod, int $min = 0)
    {
        $groups = [];

        foreach ($values as $value) {
            $carry = (int) floor(($value + $offset - $min) / $mod);

            $groups[$carry][] = $value + $offset - $carry * $mod;
        }

        return array_map(function ($group) {
            sort($group);

            return $group;
        }, $groups);
    }

    /**
     * Shift values within a cron field, wrapping around its domain.
     *
     * @param  array<int, int>  $values
     * @param  int  $offset
     * @param  int  $mod
     * @param  int  $min
     * @return array<int, int>
     */
    protected static function shiftWrapped(array $values, int $offset, int $mod, int $min = 0)
    {
        $shifted = array_values(array_unique(array_map(
            fn ($value) => (($value + $offset - $min) % $mod + $mod) % $mod + $min, $values
        )));

        sort($shifted);

        return $shifted;
    }

    /**
     * Merge grouped values into a single sorted group.
     *
     * @param  array<int, array<int, int>>  $groups
     * @return array<int, int>
     */
    protected static function mergeGroups(array $groups)
    {
        $merged = array_merge(...array_values($groups));

        sort($merged);

        return $merged;
    }

    /**
     * Collapse a sorted list of values into the most compact cron field syntax.
     *
     * @param  array<int, int>  $values
     * @param  int  $min
     * @param  int  $max
     * @return string
     */
    protected static function collapse(array $values, int $min, int $max)
    {
        if ($values === range($min, $max)) {
            return '*';
        }

        if (count($values) === 1) {
            return (string) $values[0];
        }

        $steps = (new Collection($values))
            ->sliding(2)
            ->map(fn ($pair) => $pair->last() - $pair->first())
            ->unique();

        if (count($values) < 3 || $steps->count() > 1) {
            return static::collapseRuns($values);
        }

        [$first, $last, $step] = [$values[0], end($values), $steps->first()];

        if ($step === 1) {
            return "{$first}-{$last}";
        }

        return $first === $min && $last + $step > $max
            ? "*/{$step}"
            : "{$first}-{$last}/{$step}";
    }

    /**
     * Collapse consecutive runs of three or more values into ranges.
     *
     * @param  array<int, int>  $values
     * @return string
     */
    protected static function collapseRuns(array $values)
    {
        return (new Collection($values))
            ->chunkWhile(fn ($value, $key, $chunk) => $value === $chunk->last() + 1)
            ->map(fn ($run) => $run->count() >= 3 ? $run->first().'-'.$run->last() : $run->implode(','))
            ->implode(',');
    }
}
