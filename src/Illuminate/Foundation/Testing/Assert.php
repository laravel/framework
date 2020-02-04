<?php

namespace Illuminate\Foundation\Testing;

use ArrayAccess;
use Illuminate\Foundation\Testing\Constraints\ArraySubset;
use PHPUnit\Framework\Assert as PHPUnit;
use PHPUnit\Framework\InvalidArgumentException;
use PHPUnit\Runner\Version;
use PHPUnit\Util\InvalidArgumentHelper;

if (class_exists(Version::class) && (int) Version::series()[0] >= 8) {
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
    }
} else {
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
            PHPUnit::assertArraySubset($subset, $array, $checkForIdentity, $msg);
        }
    }
}
