<?php

namespace Illuminate\Support;

use InvalidArgumentException;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Uid\Ulid;

/**
 * @phpstan-type BinaryTransform callable(?string): ?string
 * @phpstan-type BinaryCodecDefinition array{
 *      encode: BinaryTransform,
 *      decode: BinaryTransform,
 * }
 */
final class BinaryCodec
{
    /** @var array<string, BinaryCodecDefinition> */
    private static array $customCodecs = [];

    /**
     * @return array<string, BinaryCodecDefinition>
     */
    public static function all(): array
    {
        return array_replace(self::defaultCodecs(), self::$customCodecs);
    }

    /**
     * @param  BinaryTransform  $encode
     * @param  BinaryTransform  $decode
     */
    public static function register(string $name, callable $encode, callable $decode): void
    {
        self::$customCodecs[$name] = [
            'encode' => $encode,
            'decode' => $decode,
        ];
    }

    public static function encode(?string $value, string $format): ?string
    {
        return (self::callbackFor($format, 'encode'))($value);
    }

    public static function decode(?string $value, string $format): ?string
    {
        return (self::callbackFor($format, 'decode'))($value);
    }

    /** @return array<string, BinaryCodecDefinition> */
    private static function defaultCodecs(): array
    {
        return [
            'uuid' => [
                'encode' => function (?string $value) {
                    if (blank($value)) {
                        return null;
                    }

                    return (is_binary($value) ? Uuid::fromBytes($value) : Uuid::fromString($value))
                        ->getBytes();
                },
                'decode' => function (?string $value) {
                    if (blank($value)) {
                        return null;
                    }

                    return (is_binary($value) ? Uuid::fromBytes($value) : Uuid::fromString($value))
                        ->toString();
                },
            ],
            'ulid' => [
                'encode' => function (?string $value) {
                    if (blank($value)) {
                        return null;
                    }

                    return (is_binary($value) ? Ulid::fromBinary($value) : Ulid::fromString($value))
                        ->toBinary();
                },
                'decode' => function (?string $value) {
                    if (blank($value)) {
                        return null;
                    }

                    return (is_binary($value) ? Ulid::fromBinary($value) : Ulid::fromString($value))
                        ->toString();
                },
            ],
        ];
    }

    /**
     * @param  'encode'|'decode'  $direction
     * @return BinaryTransform
     */
    private static function callbackFor(string $format, string $direction): callable
    {
        $formats = self::all();

        if (! isset($formats[$format][$direction])) {
            throw new InvalidArgumentException("Format [$format] is invalid.");
        }

        return $formats[$format][$direction];
    }
}
