<?php

namespace Illuminate\Support;

use Carbon\Carbon as BaseCarbon;
use Carbon\CarbonImmutable as BaseCarbonImmutable;
use Illuminate\Support\Traits\Conditionable;
use Ramsey\Uuid\Exception\InvalidUuidStringException;
use Ramsey\Uuid\Exception\UnsupportedOperationException;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Uid\Ulid;

class Carbon extends BaseCarbon
{
    use Conditionable;

    /**
     * {@inheritdoc}
     */
    public static function setTestNow($testNow = null)
    {
        BaseCarbon::setTestNow($testNow);
        BaseCarbonImmutable::setTestNow($testNow);
    }

    /**
     * Creates a Carbon instance from the ULID or UUID.
     *
     * Returns "null" if there is no timestamp, or "false" if the UID is invalid.
     *
     * @param  \Ramsey\Uuid\Uuid|\Symfony\Component\Uid\Ulid|string  $uid
     * @return \Illuminate\Support\Carbon|null|false
     */
    public static function createFromUid($uid)
    {
        if (Ulid::isValid($uid)) {
            return static::createFromInterface(Ulid::fromString($uid)->getDateTime());
        }

        try {
            $date = Uuid::fromString($uid)->getDateTime();
        } catch (InvalidUuidStringException) {
            return false;
        } catch (UnsupportedOperationException) {
            return null;
        }

        return static::createFromInterface($date);
    }

    /**
     * Dump the instance and end the script.
     *
     * @param  mixed  ...$args
     * @return never
     */
    public function dd(...$args)
    {
        dd($this, ...$args);
    }

    /**
     * Dump the instance.
     *
     * @return $this
     */
    public function dump()
    {
        dump($this);

        return $this;
    }
}
