<?php

namespace Illuminate\Foundation\Testing;

use ArrayAccess;
use Exception;
use GrahamCampbell\TestBenchCore\ArraySubsetTrait;
use PHPUnit\Framework\Assert as PHPUnit;
use PHPUnit\Framework\Constraint\ArraySubset;
use PHPUnit\Util\InvalidArgumentHelper;
use PHPUnit\Runner\Version;

if (trait_exists(ArraySubsetTrait::class)) {
    /**
     * @internal This class is not meant to be used or overwritten outside the framework itself.
     */
    abstract class Assert extends PHPUnit
    {
        use ArraySubsetTrait;
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
         * This method was taken over from PHPUnit where it was deprecated. See link for more info.
         *
         * @param  \ArrayAccess|array  $subset
         * @param  \ArrayAccess|array  $array
         * @param  bool  $checkForObjectIdentity
         * @param  string  $message
         * @return void
         */
        public static function assertArraySubset($subset, $array, bool $checkForObjectIdentity = false, string $message = ''): void
        {
            if ((int) Version::series()[0] > 8) {
                throw new Exception('For PHPUnit 9 support, please install graham-campbell/testbench-core:"^3.1".');
            }

            if (! (is_array($subset) || $subset instanceof ArrayAccess)) {
                throw InvalidArgumentHelper::factory(1, 'array or ArrayAccess');
            }

            if (! (is_array($array) || $array instanceof ArrayAccess)) {
                throw InvalidArgumentHelper::factory(2, 'array or ArrayAccess');
            }

            $constraint = new ArraySubset($subset, $checkForObjectIdentity);

            static::assertThat($array, $constraint, $message);
        }
    }
}
