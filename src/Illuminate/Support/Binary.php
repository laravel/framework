<?php

namespace Illuminate\Support;

use InvalidArgumentException;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Uid\Ulid;

final class Binary
{
    /** @var array<string, array{encode: callable, decode: callable}> */
    private static array $customFormats = [];

    /**
     * @return array<string, array{encode: callable, decode: callable}>
     */
    public static function formats(): array
    {
        return array_replace(self::defaultFormats(), self::$customFormats);
    }

    /**
     * @param callable(?string): ?string $encode
     * @param callable(?string): ?string $decode
     */
    public static function registerFormat(string $name, callable $encode, callable $decode): void
    {
        self::$customFormats[$name] = [
            'encode' => $encode,
            'decode' => $decode,
        ];
    }

    public static function encode(?string $value, string $format): ?string
    {
        $callback = self::callbackFor($format, 'encode');

        return $callback($value);
    }

    public static function decode(?string $value, string $format): ?string
    {
        $callback = self::callbackFor($format, 'decode');

        return $callback($value);
    }

    /**
     * @return array<string, array{encode: callable, decode: callable}>
     */
    private static function defaultFormats(): array
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

    private static function callbackFor(string $format, string $direction): callable
    {
        $formats = self::formats();

        if (! isset($formats[$format][$direction])) {
            throw new InvalidArgumentException("Format [$format] is invalid.");
        }

        return $formats[$format][$direction];
    }
}
