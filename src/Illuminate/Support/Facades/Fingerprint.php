<?php

namespace Illuminate\Support\Facades;

/**
 * @method static string make(mixed $target, string|null $algorithm = null, array $options = [])
 * @method static string base64(mixed $target, string|null $algorithm = null, array $options = [])
 * @method static string base64Url(mixed $target, string|null $algorithm = null, array $options = [])
 * @method static string binary(mixed $target, string|null $algorithm = null, array $options = [])
 * @method static string hex(mixed $target, string|null $algorithm = null, array $options = [])
 * @method static bool is(mixed $expected, mixed $string)
 * @method static bool isNot(mixed $expected, mixed $string)
 *
 * @see \Illuminate\Hashing\Fingerprinter
 */
class Fingerprint extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'fingerprint';
    }
}
