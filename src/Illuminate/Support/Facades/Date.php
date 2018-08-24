<?php

namespace Illuminate\Support\Facades;

use DateTimeInterface;
use ReflectionException;
use InvalidArgumentException;
use Illuminate\Support\Carbon;

/**
 * @see https://carbon.nesbot.com/docs/
 * @see https://github.com/briannesbitt/Carbon/blob/master/src/Carbon/Factory.php
 *
 * @method static \Illuminate\Support\Carbon create($year = 0, $month = 1, $day = 1, $hour = 0, $minute = 0, $second = 0, $tz = null)
 * @method static \Illuminate\Support\Carbon createFromDate($year = null, $month = null, $day = null, $tz = null)
 * @method static \Illuminate\Support\Carbon|false createFromFormat($format, $time, $tz = null)
 * @method static \Illuminate\Support\Carbon createFromTime($hour = 0, $minute = 0, $second = 0, $tz = null)
 * @method static \Illuminate\Support\Carbon createFromTimeString($time, $tz = null)
 * @method static \Illuminate\Support\Carbon createFromTimestamp($timestamp, $tz = null)
 * @method static \Illuminate\Support\Carbon createFromTimestampMs($timestamp, $tz = null)
 * @method static \Illuminate\Support\Carbon createFromTimestampUTC($timestamp)
 * @method static \Illuminate\Support\Carbon createMidnightDate($year = null, $month = null, $day = null, $tz = null)
 * @method static \Illuminate\Support\Carbon|false createSafe($year = null, $month = null, $day = null, $hour = null, $minute = null, $second = null, $tz = null)
 * @method static \Illuminate\Support\Carbon disableHumanDiffOption($humanDiffOption)
 * @method static \Illuminate\Support\Carbon enableHumanDiffOption($humanDiffOption)
 * @method static mixed executeWithLocale($locale, $func)
 * @method static \Illuminate\Support\Carbon fromSerialized($value)
 * @method static array getAvailableLocales()
 * @method static array getDays()
 * @method static int getHumanDiffOptions()
 * @method static array getIsoUnits()
 * @method static \Illuminate\Support\Carbon getLastErrors()
 * @method static string getLocale()
 * @method static int getMidDayAt()
 * @method static \Illuminate\Support\Carbon getTestNow()
 * @method static \Symfony\Component\Translation\TranslatorInterface getTranslator()
 * @method static int getWeekEndsAt()
 * @method static int getWeekStartsAt()
 * @method static array getWeekendDays()
 * @method static bool hasFormat($date, $format)
 * @method static bool hasMacro($name)
 * @method static bool hasRelativeKeywords($time)
 * @method static bool hasTestNow()
 * @method static \Illuminate\Support\Carbon instance($date)
 * @method static bool isImmutable()
 * @method static bool isModifiableUnit($unit)
 * @method static \Illuminate\Support\Carbon isMutable()
 * @method static bool isStrictModeEnabled()
 * @method static bool localeHasDiffOneDayWords($locale)
 * @method static bool localeHasDiffSyntax($locale)
 * @method static bool localeHasDiffTwoDayWords($locale)
 * @method static bool localeHasPeriodSyntax($locale)
 * @method static bool localeHasShortUnits($locale)
 * @method static void macro($name, $macro)
 * @method static \Illuminate\Support\Carbon|null make($var)
 * @method static \Illuminate\Support\Carbon maxValue()
 * @method static \Illuminate\Support\Carbon minValue()
 * @method static void mixin($mixin)
 * @method static \Illuminate\Support\Carbon now($tz = null)
 * @method static \Illuminate\Support\Carbon parse($time = null, $tz = null)
 * @method static string pluralUnit(string $unit)
 * @method static void resetMonthsOverflow()
 * @method static void resetToStringFormat()
 * @method static void resetYearsOverflow()
 * @method static void serializeUsing($callback)
 * @method static \Illuminate\Support\Carbon setHumanDiffOptions($humanDiffOptions)
 * @method static bool setLocale($locale)
 * @method static void setMidDayAt($hour)
 * @method static \Illuminate\Support\Carbon setTestNow($testNow = null)
 * @method static void setToStringFormat($format)
 * @method static void setTranslator(\Symfony\Component\Translation\TranslatorInterface $translator)
 * @method static \Illuminate\Support\Carbon setUtf8($utf8)
 * @method static void setWeekEndsAt($day)
 * @method static void setWeekStartsAt($day)
 * @method static void setWeekendDays($days)
 * @method static bool shouldOverflowMonths()
 * @method static bool shouldOverflowYears()
 * @method static string singularUnit(string $unit)
 * @method static \Illuminate\Support\Carbon today($tz = null)
 * @method static \Illuminate\Support\Carbon tomorrow($tz = null)
 * @method static void useMonthsOverflow($monthsOverflow = true)
 * @method static \Illuminate\Support\Carbon useStrictMode($strictModeEnabled = true)
 * @method static void useYearsOverflow($yearsOverflow = true)
 * @method static \Illuminate\Support\Carbon yesterday($tz = null)
 */
class Date extends Facade
{
    /**
     * Date instance class name.
     *
     * @var string
     */
    protected static $className = Carbon::class;

    /**
     * Date interceptor.
     *
     * @var callable
     */
    protected static $interceptor;

    /**
     * Get the registered name of the component.
     *
     * @return string
     *
     * @throws \RuntimeException
     */
    protected static function getFacadeAccessor()
    {
        return 'date';
    }

    /**
     * Change the class to use for date instances.
     *
     * @param string $className
     */
    public static function use(string $className)
    {
        if (! (class_exists($className) && (in_array(DateTimeInterface::class, class_implements($className)) || method_exists($className, 'instance')))) {
            throw new InvalidArgumentException(
                'Date class must implement a public static instance(DateTimeInterface $date) method or implements DateTimeInterface.'
            );
        }

        static::$className = $className;
    }

    /**
     * Set an interceptor for each date (Carbon instance).
     *
     * @param callable $interceptor
     */
    public static function intercept(callable $interceptor)
    {
        static::$interceptor = $interceptor;
    }

    /**
     * Handle dynamic, static calls to the object.
     *
     * @param  string  $method
     * @param  array   $args
     *
     * @return mixed
     *
     * @throws \RuntimeException
     */
    public static function __callStatic($method, $args)
    {
        try {
            if (static::getFacadeRoot()) {
                return parent::__callStatic($method, $args);
            }
        } catch (ReflectionException $exception) {
            // continue
        }

        if (method_exists(static::$className, $method)) {
            $date = static::$className::$method(...$args);
        } else {
            /** @var Carbon $date */
            $date = Carbon::$method(...$args);
            if (method_exists(static::$className, 'instance')) {
                $date = static::$className::instance($date);
            } else {
                $date = new static::$className($date->format('Y-m-d H:i:s.u'), $date->getTimezone());
            }
        }

        if (static::$interceptor) {
            return call_user_func(static::$interceptor, $date);
        }

        return $date;
    }
}
