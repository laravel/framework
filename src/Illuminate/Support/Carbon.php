<?php

namespace Illuminate\Support;

use Carbon\Carbon as BaseCarbon;
use Carbon\CarbonImmutable as BaseCarbonImmutable;
use DateTimeInterface;
use Illuminate\Support\Traits\Conditionable;
use Illuminate\Support\Traits\Dumpable;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Uid\Ulid;

class Carbon extends BaseCarbon
{
    use Conditionable, Dumpable;

    /**
     * {@inheritdoc}
     */
    public static function setTestNow(mixed $testNow = null): void
    {
        BaseCarbon::setTestNow($testNow);
        BaseCarbonImmutable::setTestNow($testNow);
    }

    /**
     * Create a Carbon instance from a given ordered UUID or ULID.
     */
    public static function createFromId(Uuid|Ulid|string $id): static
    {
        if (is_string($id)) {
            $id = Ulid::isValid($id) ? Ulid::fromString($id) : Uuid::fromString($id);
        }

        return static::createFromInterface($id->getDateTime());
    }

    /**
     * Clamp the date to be within the given range.
     *
     * @param  \DateTimeInterface|string  $min
     * @param  \DateTimeInterface|string  $max
     * @return static
     */
    public function clamp(DateTimeInterface|string $min, DateTimeInterface|string $max): static
    {
        $min = $this->resolveCarbon($min);
        $max = $this->resolveCarbon($max);

        [$min, $max] = $min <= $max ? [$min, $max] : [$max, $min];

        return $this->min($max)->max($min)->copy();
    }

    /**
     * Get the current date / time plus a given amount of time.
     */
    public function plus(
        int $years = 0,
        int $months = 0,
        int $weeks = 0,
        int $days = 0,
        int $hours = 0,
        int $minutes = 0,
        int $seconds = 0,
        int $microseconds = 0,
        ?bool $overflow = null
    ): static {
        return $this->add('years', $years, $overflow)
            ->add('months', $months, $overflow)
            ->add("
                $weeks weeks $days days
                $hours hours $minutes minutes $seconds seconds $microseconds microseconds
            ");
    }

    /**
     * Get the current date / time minus a given amount of time.
     */
    public function minus(
        int $years = 0,
        int $months = 0,
        int $weeks = 0,
        int $days = 0,
        int $hours = 0,
        int $minutes = 0,
        int $seconds = 0,
        int $microseconds = 0,
        ?bool $overflow = null
    ): static {
        return $this->sub('years', $years, $overflow)
            ->sub('months', $months, $overflow)
            ->sub("
                $weeks weeks $days days
                $hours hours $minutes minutes $seconds seconds $microseconds microseconds
            ");
    }
}
