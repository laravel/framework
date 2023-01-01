<?php

namespace Illuminate\Support;

use Carbon\Factory;
use InvalidArgumentException;

/**
 * @see https://carbon.nesbot.com/docs/
 * @see https://github.com/briannesbitt/Carbon/blob/master/src/Carbon/Factory.php
 *
 * @method \Illuminate\Support\Carbon create($year = 0, $month = 1, $day = 1, $hour = 0, $minute = 0, $second = 0, $tz = null)
 * @method \Illuminate\Support\Carbon createFromDate($year = null, $month = null, $day = null, $tz = null)
 * @method \Illuminate\Support\Carbon|false createFromFormat($format, $time, $tz = null)
 * @method \Illuminate\Support\Carbon createFromTime($hour = 0, $minute = 0, $second = 0, $tz = null)
 * @method \Illuminate\Support\Carbon createFromTimeString($time, $tz = null)
 * @method \Illuminate\Support\Carbon createFromTimestamp($timestamp, $tz = null)
 * @method \Illuminate\Support\Carbon createFromTimestampMs($timestamp, $tz = null)
 * @method \Illuminate\Support\Carbon createFromTimestampUTC($timestamp)
 * @method \Illuminate\Support\Carbon createMidnightDate($year = null, $month = null, $day = null, $tz = null)
 * @method \Illuminate\Support\Carbon|false createSafe($year = null, $month = null, $day = null, $hour = null, $minute = null, $second = null, $tz = null)
 * @method \Illuminate\Support\Carbon disableHumanDiffOption($humanDiffOption)
 * @method \Illuminate\Support\Carbon enableHumanDiffOption($humanDiffOption)
 * @method mixed executeWithLocale($locale, $func)
 * @method \Illuminate\Support\Carbon fromSerialized($value)
 * @method array getAvailableLocales()
 * @method array getDays()
 * @method int getHumanDiffOptions()
 * @method array getIsoUnits()
 * @method \Illuminate\Support\Carbon getLastErrors()
 * @method string getLocale()
 * @method int getMidDayAt()
 * @method \Illuminate\Support\Carbon getTestNow()
 * @method \Symfony\Component\Translation\TranslatorInterface getTranslator()
 * @method int getWeekEndsAt()
 * @method int getWeekStartsAt()
 * @method array getWeekendDays()
 * @method bool hasFormat($date, $format)
 * @method bool hasMacro($name)
 * @method bool hasRelativeKeywords($time)
 * @method bool hasTestNow()
 * @method \Illuminate\Support\Carbon instance($date)
 * @method bool isImmutable()
 * @method bool isModifiableUnit($unit)
 * @method \Illuminate\Support\Carbon isMutable()
 * @method bool isStrictModeEnabled()
 * @method bool localeHasDiffOneDayWords($locale)
 * @method bool localeHasDiffSyntax($locale)
 * @method bool localeHasDiffTwoDayWords($locale)
 * @method bool localeHasPeriodSyntax($locale)
 * @method bool localeHasShortUnits($locale)
 * @method void macro($name, $macro)
 * @method \Illuminate\Support\Carbon|null make($var)
 * @method \Illuminate\Support\Carbon maxValue()
 * @method \Illuminate\Support\Carbon minValue()
 * @method void mixin($mixin)
 * @method \Illuminate\Support\Carbon now($tz = null)
 * @method \Illuminate\Support\Carbon parse($time = null, $tz = null)
 * @method string pluralUnit(string $unit)
 * @method void resetMonthsOverflow()
 * @method void resetToStringFormat()
 * @method void resetYearsOverflow()
 * @method void serializeUsing($callback)
 * @method \Illuminate\Support\Carbon setHumanDiffOptions($humanDiffOptions)
 * @method bool setLocale($locale)
 * @method void setMidDayAt($hour)
 * @method void setTestNow($testNow = null)
 * @method void setToStringFormat($format)
 * @method void setTranslator(\Symfony\Component\Translation\TranslatorInterface $translator)
 * @method \Illuminate\Support\Carbon setUtf8($utf8)
 * @method void setWeekEndsAt($day)
 * @method void setWeekStartsAt($day)
 * @method void setWeekendDays($days)
 * @method bool shouldOverflowMonths()
 * @method bool shouldOverflowYears()
 * @method string singularUnit(string $unit)
 * @method \Illuminate\Support\Carbon today($tz = null)
 * @method \Illuminate\Support\Carbon tomorrow($tz = null)
 * @method void useMonthsOverflow($monthsOverflow = true)
 * @method \Illuminate\Support\Carbon useStrictMode($strictModeEnabled = true)
 * @method void useYearsOverflow($yearsOverflow = true)
 * @method \Illuminate\Support\Carbon yesterday($tz = null)
 */
class DateFactory
{
    /**
     * The default class that will be used for all created dates.
     *
     * @var string
     */
    const DEFAULT_CLASS_NAME = Carbon::class;

    /**
     * The type (class) of dates that should be created.
     *
     * @var string
     */
    protected static $dateClass;

    /**
     * This callable may be used to intercept date creation.
     *
     * @var callable
     */
    protected static $callable;

    /**
     * The Carbon factory that should be used when creating dates.
     *
     * @var object
     */
    protected static $factory;

    /**
     * Use the given handler when generating dates (class name, callable, or factory).
     *
     * @param  mixed  $handler
     * @return mixed
     *
     * @throws \InvalidArgumentException
     */
    public static function use($handler)
    {
        if (is_callable($handler) && is_object($handler)) {
            return static::useCallable($handler);
        } elseif (is_string($handler)) {
            return static::useClass($handler);
        } elseif ($handler instanceof Factory) {
            return static::useFactory($handler);
        }

        throw new InvalidArgumentException('Invalid date creation handler. Please provide a class name, callable, or Carbon factory.');
    }

    /**
     * Use the default date class when generating dates.
     *
     * @return void
     */
    public static function useDefault()
    {
        static::$dateClass = null;
        static::$callable = null;
        static::$factory = null;
    }

    /**
     * Execute the given callable on each date creation.
     *
     * @param  callable  $callable
     * @return void
     */
    public static function useCallable(callable $callable)
    {
        static::$callable = $callable;

        static::$dateClass = null;
        static::$factory = null;
    }

    /**
     * Use the given date type (class) when generating dates.
     *
     * @param  string  $dateClass
     * @return void
     */
    public static function useClass($dateClass)
    {
        static::$dateClass = $dateClass;

        static::$factory = null;
        static::$callable = null;
    }

    /**
     * Use the given Carbon factory when generating dates.
     *
     * @param  object  $factory
     * @return void
     */
    public static function useFactory($factory)
    {
        static::$factory = $factory;

        static::$dateClass = null;
        static::$callable = null;
    }

    /**
     * Handle dynamic calls to generate dates.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     *
     * @throws \RuntimeException
     */
    public function __call($method, $parameters)
    {
        $defaultClassName = static::DEFAULT_CLASS_NAME;

        // Using callable to generate dates...
        if (static::$callable) {
            return call_user_func(static::$callable, $defaultClassName::$method(...$parameters));
        }

        // Using Carbon factory to generate dates...
        if (static::$factory) {
            return static::$factory->$method(...$parameters);
        }

        $dateClass = static::$dateClass ?: $defaultClassName;

        // Check if the date can be created using the public class method...
        if (method_exists($dateClass, $method) ||
            method_exists($dateClass, 'hasMacro') && $dateClass::hasMacro($method)) {
            return $dateClass::$method(...$parameters);
        }

        // If that fails, create the date with the default class...
        $date = $defaultClassName::$method(...$parameters);

        // If the configured class has an "instance" method, we'll try to pass our date into there...
        if (method_exists($dateClass, 'instance')) {
            return $dateClass::instance($date);
        }

        // Otherwise, assume the configured class has a DateTime compatible constructor...
        return new $dateClass($date->format('Y-m-d H:i:s.u'), $date->getTimezone());
    }
}
