<?php

namespace Illuminate\Support;

use Exception;
use Illuminate\Support\Traits\Conditionable;
use Illuminate\Support\Traits\Macroable;
use libphonenumber\PhoneNumber as LibPhoneNumber;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberType;
use libphonenumber\PhoneNumberUtil;
use ReflectionClass;

class PhoneNumber
{
    use Macroable, Conditionable;

    /**
     * The phone number instance.
     */
    protected PhoneNumberUtil $phoneNumberInstance;

    public function __construct(protected string $number, protected ?string $country = null)
    {
        $this->phoneNumberInstance = PhoneNumberUtil::getInstance();
    }

    /**
     * Create a new PhoneNumber instance.
     */
    public static function of(string $number, ?string $country = null): static
    {
        return new static($number, $country);
    }

    /**
     * Get the raw phone number.
     */
    public function getRawNumber(): string
    {
        return $this->number;
    }

    /**
     * Set the country code of the phone number.
     */
    public function setCountry(string $country): static
    {
        $this->country = $country;

        return $this;
    }

    /**
     * Get the country code of the phone number.
     */
    public function getCountry(): ?string
    {
        if ($this->country) {
            return $this->country;
        }

        try {
            return $this->phoneNumberInstance->getRegionCodeForNumber(
                $this->phoneNumberInstance->parse($this->number, 'ZZ')
            );
        } catch (Exception) {
            return null;
        }
    }

    /**
     * Get the type of the phone number.
     */
    public function getType(bool $asValue = false): int|string
    {
        $type = $this->phoneNumberInstance->getNumberType($this->toLibPhone());

        return $asValue ? $type : $this->getHumanReadableName($type);
    }

    /**
     * Get the lib phone number object.
     */
    public function toLibPhone(): LibPhoneNumber
    {
        return $this->phoneNumberInstance->parse(
            $this->number,
            $this->getCountry(),
        );
    }

    /**
     * Check if the phone number is valid.
     */
    public function isValid(): bool
    {
        try {
            return $this->phoneNumberInstance->isValidNumberForRegion(
                $this->toLibPhone(),
                $this->getCountry() ?? 'ZZ',
            );
        } catch (Exception) {
            return false;
        }
    }

    /**
     * Check if the phone number is of the given country.
     *
     * @param  mixed  $country
     */
    public function isOfCountry($country): bool
    {
        $countries = $this->sanitizeType(Arr::wrap($country));

        return in_array($this->getCountry(), $countries);
    }

    /**
     * Format the phone number.
     */
    public function format(string|int $format): string
    {
        $sanitizedFormat = $this->sanitizeType($format);

        if (is_null($sanitizedFormat)) {
            throw new Exception('Invalid format '.$format);
        }

        return $this->phoneNumberInstance->format(
            $this->toLibPhone(),
            $sanitizedFormat
        );
    }

    /**
     * Format the phone number in international format.
     */
    public function formatInternational(): string
    {
        return $this->format(PhoneNumberFormat::INTERNATIONAL);
    }

    /**
     * Format the phone number in national format.
     */
    public function formatNational(): string
    {
        return $this->format(PhoneNumberFormat::NATIONAL);
    }

    /**
     * Format the phone number in E164 format.
     */
    public function formatE164(): string
    {
        return $this->format(PhoneNumberFormat::E164);
    }

    /**
     * Format the phone number in RFC3966 format.
     */
    public function formatRFC3966(): string
    {
        return $this->format(PhoneNumberFormat::RFC3966);
    }

    /**
     * Format the phone number for a given country.
     *
     * @param  mixed  $country
     */
    public function formatForCountry($country): string
    {
        return $this->phoneNumberInstance->formatOutOfCountryCallingNumber(
            $this->toLibPhone(),
            $country
        );
    }

    /**
     * Format the phone number for mobile dialing in a given country.
     *
     * @param  mixed  $country
     */
    public function formatForMobileDialingInCountry($country, bool $withFormatting = false): string
    {
        return $this->phoneNumberInstance->formatNumberForMobileDialing(
            $this->toLibPhone(),
            $country,
            $withFormatting
        );
    }

    /**
     * Check if the phone number is of the given type.
     *
     * @param  mixed  $type
     */
    public function isOfType($type): bool
    {
        $types = $this->sanitizeType(Arr::wrap($type));

        if (array_intersect([PhoneNumberType::FIXED_LINE, PhoneNumberType::MOBILE], $types)) {
            $types[] = PhoneNumberType::FIXED_LINE_OR_MOBILE;
        }

        return in_array($this->getType(true), $types, true);
    }

    /**
     * Check if the type is valid.
     */
    public function isValidType($type): bool
    {
        return ! is_null($type) && in_array($type, $this->allTypes(), true);
    }

    /**
     * Check if the phone number is equal to another.
     */
    public function equals(string|PhoneNumber $number): bool
    {
        try {
            if (is_string($number)) {
                $number = new static($number);
            }

            return $this->formatE164() === $number->formatE164();
        } catch (Exception) {
            return false;
        }
    }

    /**
     * Check if the phone number is not equal to another.
     */
    public function notEquals(string|PhoneNumber $number): bool
    {
        return ! $this->equals($number);
    }

    /**
     * Convert the phone number to a string.
     */
    public function __toString(): string
    {
        try {
            return $this->formatE164();
        } catch (Exception) {
            return (string) $this->number;
        }
    }

    /**
     * Get All types of phone number.
     */
    protected function allTypes(): array
    {
        return (new ReflectionClass(PhoneNumberType::class))->getConstants();
    }

    /**
     * Get the human readable name of the type.
     */
    protected function getHumanReadableName($type): ?string
    {
        $name = array_search($type, $this->allTypes(), true);

        return $name ? strtolower($name) : null;
    }

    /**
     * Sanitize the type.
     */
    protected function sanitizeType($types): int|array|null
    {
        return Collection::make(Arr::wrap($types))
            ->map(function ($format) {
                return Arr::get($this->allTypes(), strtoupper($format), $format);
            })
            ->filter(fn ($format): bool => $this->isValidType($format))
            ->unique()
            ->when(
                is_array($types),
                fn ($collection) => $collection->toArray(),
                fn ($collection) => $collection->first(),
            );
    }
}
