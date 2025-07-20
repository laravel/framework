<?php

namespace Illuminate\Hashing;

use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Support\LazyCollection;
use Illuminate\Support\Str;
use Stringable;

class Fingerprint implements Stringable
{
    /**
     * The default algorithm to use to create non-cryptographic fingerprints.
     *
     * @var string
     */
    public static $with = 'xxh3';

    /**
     * Create a new Fingerprint instance.
     *
     * @param  mixed  $value
     * @param  string  $fingerprinter
     * @param  array  $options
     * @param  string|null  $hash
     */
    public function __construct(protected $value, protected $fingerprinter, protected $options, protected $hash = null)
    {
        //
    }

    /**
     * Returns the fingerprintable value.
     *
     * @return mixed
     */
    public function value()
    {
        return $this->value;
    }

    /**
     * Returns the algorithm used to generate a fingerprint hash.
     *
     * @return string
     */
    public function uses()
    {
        return $this->fingerprinter;
    }

    /**
     * Returns the fingerprint hash encoded in Base64.
     *
     * @return string
     */
    public function hash()
    {
        return Str::toBase64($this->raw());
    }

    /**
     * Returns the fingerprint hash as a binary string.
     */
    public function raw()
    {
        return $this->hash ??= $this->generate();
    }

    /**
     * Regenerates a fingerprint hash and returns it encoded in Base64.
     *
     * @return string
     */
    public function rehash()
    {
        $this->hash = null;

        return $this->hash();
    }

    /**
     * Generates a fingerprint hash as a binary string.
     *
     * @return string
     */
    protected function generate()
    {
        // When the value to be hashed is a simple string, we can just hash it and return the
        // result as-is. For other types of objects, we will try to normalize them to avoid
        // loading the whole buffer, especially when these have a large memory footprint.
        if (is_string($this->value) || $this->value instanceof Stringable) {
            return hash($this->fingerprinter, $this->value, true, $this->options);
        }

        $context = hash_init($this->fingerprinter, 0, '', $this->options);

        foreach ($this->normalizeValue() as $value) {
            hash_update($context, json_encode($value, JSON_THROW_ON_ERROR));
        }

        return hash_final($context, true);
    }

    /**
     * Normalize the fingerprintable value into an iterable object for hashing.
     *
     * @return iterable
     */
    protected function normalizeValue()
    {
        return match (true) {
            is_resource($this->value) => new LazyCollection(function () {
                rewind($this->value);

                while (!feof($this->value)) {
                    yield fgetc($this->value);
                }
            }),
            is_iterable($this->value) => new LazyCollection(function () {
                foreach ($this->value as $value) {
                    yield $value;
                }
            }),
            is_array($this->value) => $this->value,
            default => [$this->value],
        };
    }

    /**
     * Determines if this fingerprint hash and the issued hash are the same.
     *
     * @param  string  $hash
     * @param  bool  $fromBase64
     * @return bool
     */
    public function is($hash, $fromBase64 = true)
    {
        return hash_equals($this->raw(), $fromBase64 || $hash instanceof self ? Str::fromBase64($hash) : $hash);
    }

    /**
     * Determines if this fingerprint hash and the issued hash are different.
     *
     * @param  string  $hash
     * @param  bool  $fromBase64
     * @return bool
     */
    public function isNot($hash, $fromBase64 = true)
    {
        return !$this->is($hash, $fromBase64);
    }

    /**
     * Returns the string representation of the object.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->hash();
    }

    /**
     * Create a new Fingerprint instance.
     *
     * @param  mixed  $value
     * @param  string|null  $algorithm
     * @param  array  $options
     * @return static
     */
    public static function of($value, $algorithm = null, $options = [])
    {
        return new static($value, $algorithm ?? static::$with, $options);
    }
}
