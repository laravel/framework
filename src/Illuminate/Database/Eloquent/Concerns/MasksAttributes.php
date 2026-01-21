<?php

namespace Illuminate\Database\Eloquent\Concerns;

use Illuminate\Support\Str;

trait MasksAttributes
{
    /**
     * The attributes that should be masked when retrieved.
     *
     * @var array<int, string>
     */
    protected $masked = [];

    /**
     * Indicates if masking is currently disabled.
     *
     * @var bool
     */
    protected $withoutMasking = false;

    /**
     * Get the masked attributes for the model.
     *
     * @return array<int, string>
     */
    public function getMasked()
    {
        return $this->masked;
    }

    /**
     * Set the masked attributes for the model.
     *
     * @param  array<int, string>  $masked
     * @return $this
     */
    public function setMasked(array $masked)
    {
        $this->masked = $masked;

        return $this;
    }

    /**
     * Merge new masked attributes with existing masked attributes on the model.
     *
     * @param  array<int, string>  $masked
     * @return $this
     */
    public function mergeMasked(array $masked)
    {
        $this->masked = array_values(array_unique(array_merge($this->masked, $masked)));

        return $this;
    }

    /**
     * Temporarily disable masking on the model.
     *
     * @param  array<int, string>|string|null  $attributes
     * @return $this
     */
    public function withoutMasking($attributes = null)
    {
        if ($attributes === null) {
            $this->withoutMasking = true;

            return $this;
        }

        $attributes = is_array($attributes) ? $attributes : func_get_args();

        $this->masked = array_diff($this->masked, $attributes);

        return $this;
    }

    /**
     * Run a callback with masking disabled on the model.
     *
     * @param  callable  $callback
     * @return mixed
     */
    public function withoutMaskingCallback(callable $callback)
    {
        $this->withoutMasking = true;

        try {
            return $callback($this);
        } finally {
            $this->withoutMasking = false;
        }
    }

    /**
     * Determine if the given attribute should be masked.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return bool
     */
    protected function shouldMaskAttribute(string $key, mixed $value): bool
    {
        return ! $this->withoutMasking
            && in_array($key, $this->masked, true)
            && is_string($value)
            && $value !== '';
    }

    /**
     * Mask the given attribute value.
     *
     * @param  string  $key
     * @param  string  $value
     * @return string
     */
    protected function maskAttributeValue(string $key, string $value): string
    {
        // Auto-detect email addresses
        if (Str::contains($value, '@') && filter_var($value, FILTER_VALIDATE_EMAIL)) {
            return $this->maskEmail($value);
        }

        // Default text/phone masking
        return $this->maskText($value);
    }

    /**
     * Mask an email address.
     *
     * @param  string  $email
     * @return string
     */
    protected function maskEmail(string $email): string
    {
        [$name, $domain] = explode('@', $email, 2);

        $maskedName = strlen($name) > 2
            ? substr($name, 0, 1).str_repeat('*', max(strlen($name) - 2, 1)).substr($name, -1)
            : str_repeat('*', strlen($name));

        return $maskedName.'@'.$domain;
    }

    /**
     * Mask a text value.
     *
     * @param  string  $value
     * @param  int  $visibleStart
     * @param  int  $visibleEnd
     * @return string
     */
    protected function maskText(string $value, int $visibleStart = 3, int $visibleEnd = 3): string
    {
        $length = strlen($value);

        if ($length <= ($visibleStart + $visibleEnd)) {
            return str_repeat('*', $length);
        }

        return substr($value, 0, $visibleStart)
            .str_repeat('*', $length - ($visibleStart + $visibleEnd))
            .substr($value, -$visibleEnd);
    }
}
