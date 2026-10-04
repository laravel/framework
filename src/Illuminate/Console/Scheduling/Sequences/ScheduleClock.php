<?php

namespace Illuminate\Console\Scheduling\Sequences;

use Carbon\CarbonImmutable;
use DateInterval;
use DateTimeZone;
use Illuminate\Console\Scheduling\Sequences\Models\ScheduledSequence;
use InvalidArgumentException;

class ScheduleClock
{
    /** @param list<string> $offsets */
    public function validate(array $offsets, ?int $repeat, CarbonImmutable $start, string $timezone): void
    {
        new DateTimeZone($timezone);

        if ($offsets === [] || ! array_is_list($offsets) || ($repeat !== null && $repeat < 1)) {
            throw new InvalidArgumentException('Provide ordered offsets and a positive recurrence interval.');
        }

        $previous = null;
        foreach ($offsets as $offset) {
            $date = $this->offset($start, $timezone, $offset);
            if ($date->lt($start) || ($previous !== null && $date->lte($previous))) {
                throw new InvalidArgumentException('Offsets must be non-negative and strictly increasing.');
            }
            $previous = $date;
        }
    }

    public function offset(CarbonImmutable $start, string $timezone, string $offset): CarbonImmutable
    {
        return $start->setTimezone($timezone)->add(new DateInterval($offset))->utc();
    }

    /** @return array{number: int, at: ?CarbonImmutable} */
    public function next(ScheduledSequence $sequence, CarbonImmutable $now): array
    {
        $number = $sequence->next_number + 1;
        $offsets = $sequence->offsets;

        if ($number < count($offsets)) {
            return ['number' => $number, 'at' => $this->offset($sequence->starts_at, $sequence->timezone, $offsets[$number])];
        }

        if ($sequence->repeat_seconds === null) {
            return ['number' => $number, 'at' => null];
        }

        $anchor = $this->offset($sequence->starts_at, $sequence->timezone, $offsets[array_key_last($offsets)]);
        $interval = $sequence->repeat_seconds;
        $step = max(
            $number - count($offsets) + 1,
            intdiv(max(0, $now->getTimestamp() - $anchor->getTimestamp()), $interval) + 1,
        );

        return [
            'number' => count($offsets) - 1 + $step,
            'at' => $anchor->addSeconds($step * $interval),
        ];
    }
}
