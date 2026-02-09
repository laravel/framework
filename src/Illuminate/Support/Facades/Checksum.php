<?php

namespace Illuminate\Support\Facades;

use Mockery;

/**
 * @method static \Illuminate\Hashing\Checksum withSeed(string|int $seed)
 * @method static \Illuminate\Hashing\Checksum withSecret(string $secret)
 * @method static \Illuminate\Hashing\Checksum crc32(\Illuminate\Contracts\Support\Jsonable|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable|\SplFileInfo|resource|string $data)
 * @method static \Illuminate\Hashing\Checksum md5(\Illuminate\Contracts\Support\Jsonable|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable|\SplFileInfo|resource|string $data)
 * @method static \Illuminate\Hashing\Checksum sha256(\Illuminate\Contracts\Support\Jsonable|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable|\SplFileInfo|resource|string $data)
 * @method static \Illuminate\Hashing\Checksum xxh3(\Illuminate\Contracts\Support\Jsonable|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable|\SplFileInfo|resource|string $data)
 * @method static \Illuminate\Hashing\Checksum xxh128(\Illuminate\Contracts\Support\Jsonable|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable|\SplFileInfo|resource|string $data)
 * @method static bool isEqualTo(\Illuminate\Contracts\Support\Jsonable|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable|\SplFileInfo|resource|string $comparable)
 * @method static bool isNotEqualTo(\Illuminate\Contracts\Support\Jsonable|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable|\SplFileInfo|resource|string $comparable)
 * @method static string hash()
 * @method static string toString()
 * @method static string toBinary()
 *
 * @see \Illuminate\Hashing\Checksum
 */
class Checksum extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'checksum';
    }
}
