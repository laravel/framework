<?php

namespace Illuminate\Validation\Rules;

use Illuminate\Support\Arr;
use Illuminate\Support\Traits\Conditionable;
use Stringable;

class StringRule implements Stringable
{
    use Conditionable;

    /**
     * The constraints for the number rule.
     */
    protected array $constraints = ['string'];

    /**
     * The field under validation must be entirely Unicode alphabetic characters.
     *
     * @param  bool  $onlyAscii
     * @return $this
     */
    public function alpha(bool $onlyAscii = false): static
    {
        return $this->addRule('alpha'.($onlyAscii ? ':ascii' : ''));
    }

    /**
     * The field under validation must be entirely Unicode alpha-numeric characters.
     *
     * @param  bool  $onlyAscii
     * @return $this
     */
    public function alphaNumeric(bool $onlyAscii = false): static
    {
        return $this->addRule('alpha_num'.($onlyAscii ? ':ascii' : ''));
    }

    /**
     * The field under validation must be entirely Unicode alpha-numeric characters and dash, underscore.
     *
     * @param  bool  $onlyAscii
     * @return $this
     */
    public function alphaDash(bool $onlyAscii = false): static
    {
        return $this->addRule('alpha_dash'.($onlyAscii ? ':ascii' : ''));
    }

    /**
     * The field under validation must be entirely 7-bit ASCII characters.
     *
     * @return $this
     */
    public function ascii(): static
    {
        return $this->addRule('ascii');
    }

    /**
     * The field under validation must contain a valid color value in hexadecimal format.
     *
     * @return $this
     */
    public function hexColor(): static
    {
        return $this->addRule('hex_color');
    }

    /**
     * The field under validation must be an IP address.
     *
     * @param  int|null  $ver
     * @return $this
     */
    public function ipAddress(?int $ver = null): static
    {
        $rule = match ($ver) {
            4 => 'ipv4',
            6 => 'ipv6',
            default => 'ip',
        };

        return $this->addRule($rule);
    }

    /**
     * The field under validation must be an IPv4 address.
     *
     * @return $this
     */
    public function ipv4(): static
    {
        return $this->ipAddress(4);
    }

    /**
     * The field under validation must be an IPv6 address.
     *
     * @return $this
     */
    public function ipv6(): static
    {
        return $this->ipAddress(6);
    }

    /**
     * The field under validation must be a MAC address.
     *
     * @return $this
     */
    public function macAddress(): static
    {
        return $this->addRule('mac_address');
    }

    /**
     * The field under validation must be a valid JSON string.
     *
     * @return $this
     */
    public function json(): static
    {
        return $this->addRule('json');
    }

    /**
     * The field under validation must not start with one of the given values.
     *
     * @param  array|mixed  $values
     * @return $this
     */
    public function doesntStartWith($values): static
    {
        $values = is_array($values) ? $values : func_get_args();

        return $this->addRule('doesnt_start_with:'.Arr::join($values, ','));
    }

    /**
     * The field under validation must not end with one of the given values.
     *
     * @param  array|mixed  $values
     * @return $this
     */
    public function doesntEndWith($values): static
    {
        $values = is_array($values) ? $values : func_get_args();

        return $this->addRule('doesnt_end_with:'.Arr::join($values, ','));
    }

    /**
     * The field under validation must start with one of the given values.
     *
     * @param  array|mixed  $values
     * @return $this
     */
    public function startsWith($values): static
    {
        $values = is_array($values) ? $values : func_get_args();

        return $this->addRule('starts_with:'.Arr::join($values, ','));
    }

    /**
     * The field under validation must end with one of the given values.
     *
     * @param  array|mixed  $values
     * @return $this
     */
    public function endsWith($values): static
    {
        $values = is_array($values) ? $values : func_get_args();

        return $this->addRule('ends_with:'.Arr::join($values, ','));
    }

    /**
     * The field under validation must be lowercase.
     *
     * @return $this
     */
    public function lowercase(): static
    {
        return $this->addRule('lowercase');
    }

    /**
     * The field under validation must be uppercase.
     *
     * @return $this
     */
    public function uppercase(): static
    {
        return $this->addRule('uppercase');
    }

    /**
     * The given field must have length equal to the given value.
     *
     * @param  int  $length
     * @return $this
     */
    public function length(int $length): static
    {
        return $this->addRule('size:'.$length);
    }

    /**
     * The field under validation must have length less than or equal to the given value.
     *
     * @param  int  $max
     * @return $this
     */
    public function maxLength(int $max): static
    {
        return $this->addRule('max:'.$max);
    }

    /**
     * The field under validation must have length greater than or equal to the given value.
     *
     * @param  int  $min
     * @return $this
     */
    public function minLength(int $min): static
    {
        return $this->addRule('min:'.$min);
    }

    /**
     * The field under validation must have a different value than field.
     *
     * @param  string  $field
     * @return $this
     */
    public function different(string $field): static
    {
        return $this->addRule('different:'.$field);
    }

    /**
     * The given field must match the field under validation.
     *
     * @param  string  $field
     * @return $this
     */
    public function same(string $field): static
    {
        return $this->addRule('same:'.$field);
    }

    /**
     * The field under validation must be a valid A or AAAA record.
     *
     * @return $this
     */
    public function activeUrl(): static
    {
        return $this->addRule('active_url');
    }

    /**
     * The field under validation must be a valid URL.
     *
     * @param  array|mixed  $protocols
     * @return $this
     */
    public function url($protocols = []): static
    {
        $protocols = is_array($protocols) ? $protocols : func_get_args();

        return $this->addRule('url'.($protocols ? ':'.Arr::join($protocols, ',') : ''));
    }

    /**
     * The field under validation must be a valid ULID.
     *
     * @return $this
     */
    public function ulid(): static
    {
        return $this->addRule('ulid');
    }

    /**
     * The field under validation must be a valid UUID.
     *
     * @return $this
     */
    public function uuid(): static
    {
        return $this->addRule('uuid');
    }

    /**
     * Convert the rule to a validation string.
     *
     * @return string
     */
    public function __toString(): string
    {
        return implode('|', array_unique($this->constraints));
    }

    /**
     * Add custom rules to the validation rules array.
     *
     * @param  array|string  $rules
     * @return $this
     */
    protected function addRule(array|string $rules): static
    {
        $this->constraints = array_merge($this->constraints, Arr::wrap($rules));

        return $this;
    }
}
