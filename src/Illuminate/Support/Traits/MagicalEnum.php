<?php

namespace Illuminate\Support\Traits;

use BadMethodCallException;

trait MagicalEnum
{
    /**
     * Checks if the class represents an enumeration type.
     *
     * @return bool
     */
    public static function isEnum(): bool
    {
        return enum_exists(self::class);
    }

    /**
     * Counts the number of cases in the enumeration type.
     *
     * @return int
     */
    public static function count(): int
    {
        self::ensureEnum();

        return count(self::cases());
    }

    /**
     * Checks if the enumeration type is backed by a specific backing type.
     *
     * @return bool
     */
    public static function isBackedEnum(): bool
    {
        self::ensureEnum();

        return (new \ReflectionEnum(self::class))->isBacked();
    }


    /**
     *  Retrieves the enumeration type (int,string).
     *
     * @return string|null
     */
    public static function getBackingType(): ?string
    {
        self::ensureEnum();

        return (new \ReflectionEnum(self::class))->getBackingType()?->getName();
    }

    /**
     * Checks if the enumeration type implements a specified interface
     *
     * @param string $interfaceName
     * @return bool
     */
    public static function isImplementsInterface(string $interfaceName): bool
    {
        self::ensureEnum();

        return is_a(self::class, $interfaceName, true);
    }

    /**
     * Checks if a specified trait is used in the current enum
     *
     * @param string $traitName
     * @return bool
     */
    public static function isTraitUsed(string $traitName): bool
    {
        return in_array($traitName, class_uses(self::class), true);
    }

    /**
     * Retrieves an array of names representing the cases of the enumeration.
     *
     * @return array
     */
    public static function names(): array
    {
        self::ensureEnum();

        return array_column(self::cases(), 'name');
    }

    /**
     * Retrieves an array of values representing the cases of the enumeration.
     *
     * @return array
     */
    public static function values(): array
    {
        self::ensureEnum();

        return self::isBackedEnum()
            ? array_column(self::cases(), 'value')
            : [];
    }


    /**
     * Checks if the enumeration type has a specific case by name.
     *
     * @param string $name
     * @return bool
     */
    public static function hasCase(string $name): bool
    {
        self::ensureEnum();

        return (new \ReflectionEnum(self::class))->hasCase($name);
    }

    /**
     * Validates a value for an enumeration type.
     *
     * @param string|int $value
     * @return bool
     */
    public static function isValidEnumValue(string|int $value): bool
    {
        self::ensureEnum();

        if (self::isBackedEnum() === true) {
            return self::tryFrom($value) !== null;
        }

        return false;
    }

    /**
     * Converts the enumeration type to an associative array.
     *
     * @return array
     */
    public static function toArray(): array
    {
        self::ensureEnum();

        if (self::isBackedEnum() === false) {
            return self::names();
        }

        return array_column(self::cases(), 'value', 'name');
    }

    /**
     * Generates an associative array mapping case values to their corresponding names in the enumeration type.
     *
     * @return array
     */
    public static function reverseArray(): array
    {
        self::ensureEnum();

        if (self::isBackedEnum() === false) {
            return self::names();
        }

        return array_column(self::cases(), 'name', 'value');
    }

    private static function ensureEnum(): void
    {
        if (self::isEnum() === false) {
            throw new BadMethodCallException('This method should only be called within an enumeration.');
        }
    }
}
