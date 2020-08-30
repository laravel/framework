<?php

namespace Illuminate\Testing;

use ArrayAccess;
use Illuminate\Testing\Constraints\ArraySubset;
use PHPUnit\Framework\Assert as PHPUnit;
use PHPUnit\Framework\Constraint\DirectoryExists;
use PHPUnit\Framework\Constraint\FileExists;
use PHPUnit\Framework\Constraint\LogicalNot;
use PHPUnit\Framework\Constraint\RegularExpression;
use PHPUnit\Framework\InvalidArgumentException;
use PHPUnit\Util\InvalidArgumentHelper;

/**
 * @internal This class is not meant to be used or overwritten outside the framework itself.
 */
abstract class Assert extends PHPUnit
{
    /**
     * Asserts that an array has a specified subset.
     *
     * @param  \ArrayAccess|array  $subset
     * @param  \ArrayAccess|array  $array
     * @param  bool  $checkForIdentity
     * @param  string  $msg
     * @return void
     */
    public static function assertArraySubset($subset, $array, bool $checkForIdentity = false, string $msg = ''): void
    {
        if (! (is_array($subset) || $subset instanceof ArrayAccess)) {
            if (class_exists(InvalidArgumentException::class)) {
                throw InvalidArgumentException::create(1, 'array or ArrayAccess');
            } else {
                throw InvalidArgumentHelper::factory(1, 'array or ArrayAccess');
            }
        }

        if (! (is_array($array) || $array instanceof ArrayAccess)) {
            if (class_exists(InvalidArgumentException::class)) {
                throw InvalidArgumentException::create(2, 'array or ArrayAccess');
            } else {
                throw InvalidArgumentHelper::factory(2, 'array or ArrayAccess');
            }
        }

        $constraint = new ArraySubset($subset, $checkForIdentity);

        PHPUnit::assertThat($array, $constraint, $msg);
    }

    /**
     * Asserts that a file does not exist.
     *
     * @param  string  $filename
     * @param  string  $message
     * @return void
     */
    public static function assertFileDoesNotExist(string $filename, string $message = ''): void
    {
        static::assertThat($filename, new LogicalNot(new FileExists), $message);
    }

    /**
     * Asserts that a directory does not exist.
     *
     * @param  string  $directory
     * @param  string  $message
     * @return void
     */
    public static function assertDirectoryDoesNotExist(string $directory, string $message = ''): void
    {
        static::assertThat($directory, new LogicalNot(new DirectoryExists), $message);
    }

    /**
     * Asserts that a string matches a given regular expression.
     *
     * @param  string  $pattern
     * @param  string  $string
     * @param  string  $message
     * @return void
     */
    public static function assertMatchesRegularExpression(string $pattern, string $string, string $message = ''): void
    {
        static::assertThat($string, new RegularExpression($pattern), $message);
    }
}
