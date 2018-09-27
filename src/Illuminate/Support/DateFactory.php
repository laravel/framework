<?php

namespace Illuminate\Support;

use InvalidArgumentException;

/**
 * @see https://carbon.nesbot.com/docs/
 * @see https://github.com/briannesbitt/Carbon/blob/master/src/Carbon/Factory.php
 *
 * @method static Carbon create($year = 0, $month = 1, $day = 1, $hour = 0, $minute = 0, $second = 0, $tz = null)
 * @method static Carbon createFromDate($year = null, $month = null, $day = null, $tz = null)
 * @method static Carbon|false createFromFormat($format, $time, $tz = null)
 * @method static Carbon createFromTime($hour = 0, $minute = 0, $second = 0, $tz = null)
 * @method static Carbon createFromTimeString($time, $tz = null)
 * @method static Carbon createFromTimestamp($timestamp, $tz = null)
 * @method static Carbon createFromTimestampMs($timestamp, $tz = null)
 * @method static Carbon createFromTimestampUTC($timestamp)
 * @method static Carbon createMidnightDate($year = null, $month = null, $day = null, $tz = null)
 * @method static Carbon|false createSafe($year = null, $month = null, $day = null, $hour = null, $minute = null, $second = null, $tz = null)
 * @method static Carbon disableHumanDiffOption($humanDiffOption)
 * @method static Carbon enableHumanDiffOption($humanDiffOption)
 * @method static mixed executeWithLocale($locale, $func)
 * @method static Carbon fromSerialized($value)
 * @method static array getAvailableLocales()
 * @method static array getDays()
 * @method static int getHumanDiffOptions()
 * @method static array getIsoUnits()
 * @method static Carbon getLastErrors()
 * @method static string getLocale()
 * @method static int getMidDayAt()
 * @method static Carbon getTestNow()
 * @method static \Symfony\Component\Translation\TranslatorInterface getTranslator()
 * @method static int getWeekEndsAt()
 * @method static int getWeekStartsAt()
 * @method static array getWeekendDays()
 * @method static bool hasFormat($date, $format)
 * @method static bool hasMacro($name)
 * @method static bool hasRelativeKeywords($time)
 * @method static bool hasTestNow()
 * @method static Carbon instance($date)
 * @method static bool isImmutable()
 * @method static bool isModifiableUnit($unit)
 * @method static Carbon isMutable()
 * @method static bool isStrictModeEnabled()
 * @method static bool localeHasDiffOneDayWords($locale)
 * @method static bool localeHasDiffSyntax($locale)
 * @method static bool localeHasDiffTwoDayWords($locale)
 * @method static bool localeHasPeriodSyntax($locale)
 * @method static bool localeHasShortUnits($locale)
 * @method static void macro($name, $macro)
 * @method static Carbon|null make($var)
 * @method static Carbon maxValue()
 * @method static Carbon minValue()
 * @method static void mixin($mixin)
 * @method static Carbon now($tz = null)
 * @method static Carbon parse($time = null, $tz = null)
 * @method static string pluralUnit(string $unit)
 * @method static void resetMonthsOverflow()
 * @method static void resetToStringFormat()
 * @method static void resetYearsOverflow()
 * @method static void serializeUsing($callback)
 * @method static Carbon setHumanDiffOptions($humanDiffOptions)
 * @method static bool setLocale($locale)
 * @method static void setMidDayAt($hour)
 * @method static Carbon setTestNow($testNow = null)
 * @method static void setToStringFormat($format)
 * @method static void setTranslator(\Symfony\Component\Translation\TranslatorInterface $translator)
 * @method static Carbon setUtf8($utf8)
 * @method static void setWeekEndsAt($day)
 * @method static void setWeekStartsAt($day)
 * @method static void setWeekendDays($days)
 * @method static bool shouldOverflowMonths()
 * @method static bool shouldOverflowYears()
 * @method static string singularUnit(string $unit)
 * @method static Carbon today($tz = null)
 * @method static Carbon tomorrow($tz = null)
 * @method static void useMonthsOverflow($monthsOverflow = true)
 * @method static Carbon useStrictMode($strictModeEnabled = true)
 * @method static void useYearsOverflow($yearsOverflow = true)
 * @method static Carbon yesterday($tz = null)
 */
class DateFactory
{
    /**
     * @const string Default class to use to create dates and to handle inner static calls not possibly not supported by
     *               the class in use.
     */
    const DEFAULT_CLASS_NAME = Carbon::class;

    /**
     * @var callable function/closure to use to intercept date creation.
     */
    protected static $callable = null;

    /**
     * @var string class name to convert dates to.
     */
    protected static $className = null;

    /**
     * @var object factory to use to generate dates.
     */
    protected static $factory = null;

    /**
     * Use the given callable to intercept date on creation.
     *
     * @param callable $callable
     */
    public static function useCallable(callable $callable)
    {
        static::$className = null;
        static::$factory = null;
        static::$callable = $callable;
    }

    /**
     * Use the given different class name for date generation.
     *
     * @param string $className
     */
    public static function useClassName($className)
    {
        static::$className = $className;
        static::$factory = null;
        static::$callable = null;
    }

    /**
     * Use the given factory for date generation.
     *
     * @param $factory
     */
    public static function useFactory($factory)
    {
        static::$className = null;
        static::$factory = $factory;
        static::$callable = null;
    }

    /**
     * Use the given date handler for date generation/interception (can be either a callable, a class name or a
     * factory).
     *
     * @param $dateHandler
     */
    public static function use($dateHandler)
    {
        if (is_callable($dateHandler)) {
            static::useCallable($dateHandler);
        } elseif (is_string($dateHandler)) {
            static::useClassName($dateHandler);
        } elseif (is_object($dateHandler)) {
            static::useFactory($dateHandler);
        } else {
            throw new InvalidArgumentException('Invalid type of date handler used.');
        }
    }

    /**
     * Handle dynamic calls to generate dates.
     *
     * @param  string  $method
     * @param  array   $args
     *
     * @return mixed
     *
     * @throws \RuntimeException
     */
    public function __call($method, $args)
    {
        $defaultClassName = static::DEFAULT_CLASS_NAME;

        // If Date::use(function () { ... }) with some closure or any callable has been called
        if (static::$callable) {
            // Then we create the date with Carbon then we pass it to this callable that can modify or replace
            // This date object with any other value
            return call_user_func(static::$callable, $defaultClassName::$method(...$args));
        }

        // If Date::use($factory) with a factory instance has been called (such as a Carbon\Factory instance)
        if (static::$factory) {
            // Then we create the date with Carbon then we pass it to this callable that can modify or replace
            // This date object with any other value
            return static::$factory->$method(...$args);
        }

        // If Date::use(SomeClass::class) or by default (Carbon class)
        $className = static::$className ?: $defaultClassName;

        // If the given class support the method has a public static one, then we immediately call it and return
        // the result. When using the default settings ($root == Carbon::class) then we enter this path.
        if (method_exists($className, $method)) {
            return $className::$method(...$args);
        }

        // Else, we create the date with default class (Carbon)
        /** @var Carbon $date */
        $date = $defaultClassName::$method(...$args);

        // If the given class has a public method `instance` (Carbon sub-class for example), then we use it
        // to convert the object to an instance of the the chosen class
        if (method_exists($className, 'instance')) {
            return $className::instance($date);
        }

        // If the given class has no public method `instance`, we assume it has a DateTime compatible
        // constructor and so we use it to create the new instance.
        return new $className($date->format('Y-m-d H:i:s.u'), $date->getTimezone());
    }
}
