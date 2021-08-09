<?php

namespace Illuminate\Support;

use Carbon\Carbon as BaseCarbon;
use Carbon\CarbonImmutable as BaseCarbonImmutable;
use Carbon\Exceptions\InvalidArgumentException;
use Illuminate\Contracts\Routing\UrlRoutable;

class Carbon extends BaseCarbon implements UrlRoutable
{
    /**
     * {@inheritdoc}
     */
    public static function setTestNow($testNow = null)
    {
        BaseCarbon::setTestNow($testNow);
        BaseCarbonImmutable::setTestNow($testNow);
    }

    /**
     * {@inheritdoc}
     */
    public function getRouteKey()
    {
        return 'date';
    }

    /**
     * {@inheritdoc}
     */
    public function getRouteKeyName()
    {
        return $this->getRouteKey();
    }

    /**
     * {@inheritdoc}
     */
    public function resolveRouteBinding($value, $field = null)
    {
        try {
            return static::createFromFormat($field ?? '!Y-m-d', $value);
        } catch (InvalidArgumentException $e) {
            return null;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function resolveChildRouteBinding($childType, $value, $field)
    {
        return null;
    }
}
