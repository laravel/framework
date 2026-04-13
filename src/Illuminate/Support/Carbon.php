<?php

namespace Illuminate\Support;

use Carbon\Carbon as BaseCarbon;
use Carbon\CarbonImmutable as BaseCarbonImmutable;
use Exception;
use Illuminate\Support\Traits\Conditionable;
use Illuminate\Support\Traits\Dumpable;
use Symfony\Component\Uid\BinaryUtil;
use Symfony\Component\Uid\TimeBasedUidInterface;
use Symfony\Component\Uid\Ulid;
use Symfony\Component\Uid\Uuid;

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

        if (Str::isUuid($uuid = $id->toString(), 2)) {
            return static::createFromInterface(
                BinaryUtil::hexToDateTime('0'.substr($uuid, 15, 3).substr($uuid, 9, 4).'00000000')
            );
        }

        if (! $id instanceof TimeBasedUidInterface) {
            throw new Exception("Not a time-based UUID or ULID: [{$id->toString()}].");
        }

        return static::createFromInterface($id->getDateTime());
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
