<?php

namespace Illuminate\Support;

use InvalidArgumentException;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Uid\Ulid;

class BinaryCodec
{
    /** @var array<string, array{encode: callable(?string): ?string, decode: callable(?string): ?string}> */
    protected static array $customCodecs = [];

    /**
     * Register a custom codec.
     */
    public static function register(string $name, callable $encode, callable $decode): void
    {
        self::$customCodecs[$name] = [
            'encode' => $encode,
            'decode' => $decode,
        ];
    }

    /**
     * Encode a value to binary.
     */
    public static function encode(?string $value, string $format): ?string
    {
        if (blank($value)) {
            return null;
        }

        if (isset(self::$customCodecs[$format])) {
            return (self::$customCodecs[$format]['encode'])($value);
        }

        return match ($format) {
            'uuid' => (is_binary($value) ? Uuid::fromBytes($value) : Uuid::fromString($value))->getBytes(),
            'ulid' => (is_binary($value) ? Ulid::fromBinary($value) : Ulid::fromString($value))->toBinary(),
            default => throw new InvalidArgumentException("Format [$format] is invalid."),
        };
    }

    /**
     * Decode a binary value to string.
     */
    public static function decode(?string $value, string $format): ?string
    {
        if (blank($value)) {
            return null;
        }

        if (isset(self::$customCodecs[$format])) {
            return (self::$customCodecs[$format]['decode'])($value);
        }

        return match ($format) {
            'uuid' => (is_binary($value) ? Uuid::fromBytes($value) : Uuid::fromString($value))->toString(),
            'ulid' => (is_binary($value) ? Ulid::fromBinary($value) : Ulid::fromString($value))->toString(),
            default => throw new InvalidArgumentException("Format [$format] is invalid."),
        };
    }

    /**
     * Get all available format names.
     *
     * @return list<string>
     */
    public static function formats(): array
    {
        return array_unique([...['uuid', 'ulid'], ...array_keys(self::$customCodecs)]);
    }
}
