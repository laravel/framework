<?php

namespace Illuminate\Database\Eloquent;

use UnhandledMatchError;

enum ModelKeyType: string
{
    case INT = 'int';
    case STRING = 'string';
    case BINARY = 'binary';

    public static function create(string $value): self
    {
        return match (true) {
            str_starts_with($value, 'int') => self::INT,
            default => self::from($value),
        };
    }

    public static function try(string $value): ?self
    {
        try {
            return self::create($value);
        } catch (UnhandledMatchError) {
            return null;
        }
    }

    public function isInt(): bool
    {
        return $this === self::INT;
    }

    public function isBinary(): bool
    {
        return $this === self::BINARY;
    }
}
