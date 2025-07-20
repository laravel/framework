<?php

namespace Illuminate\Hashing;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Support\LazyCollection;
use JsonSerializable;
use Stringable;
use function hash;
use function json_encode;

class Fingerprinter
{
    /**
     * Create a new Fingerprinter instance.
     */
    public function __construct(protected string $algorithm)
    {
        //
    }

    /**
     * Returns the fingerprint hash encoded in Base64.
     *
     * @param  mixed  $target
     * @param  string|null  $algorithm
     * @param  array  $options
     * @return string
     */
    public function make($target, $algorithm = null, array $options = [])
    {
        return base64_encode($this->generate($target, $algorithm, $options));
    }

    /**
     * Returns the fingerprint hash encoded in Base64.
     *
     * @param  mixed  $target
     * @param  string|null  $algorithm
     * @param  array  $options
     * @return string
     */
    public function base64($target, $algorithm = null, $options = [])
    {
        return $this->make($target, $algorithm, $options);
    }

    /**
     * Returns the fingerprint hash encoded as Base64 with URL-safe characters.
     *
     * @param  mixed  $target
     * @param  string|null  $algorithm
     * @param  array  $options
     * @return string
     */
    public function base64Url($target, $algorithm = null, $options = [])
    {
        return rtrim(strtr($this->binary($target, $algorithm, $options), ['+' => '-', '/' => '_']), '=');
    }

    /**
     * Returns the fingerprint hash as a binary string.
     *
     * @param  mixed  $target
     * @param  string|null  $algorithm
     * @param  array  $options
     * @return string
     */
    public function binary($target, $algorithm = null, $options = [])
    {
        return $this->generate($target, $algorithm, $options);
    }

    /**
     * Returns the fingerprint hash as a hexadecimal string.
     *
     * @param  mixed  $target
     * @param  string|null  $algorithm
     * @param  array  $options
     * @return string
     */
    public function hex($target, $algorithm = null, $options = [])
    {
        return bin2hex($this->binary($target, $algorithm, $options));
    }

    /**
     * Generates a fingerprint hash as a binary string.
     *
     * @param  mixed  $target
     * @param  string|null  $algorithm
     * @param  array  $options
     * @return string
     */
    protected function generate($target, $algorithm, $options)
    {
        if (!$target) {
            return '';
        }

        if (!$algorithm) {
            $algorithm = $this->algorithm;
        }

        // When the target can be represented as a string, we can just hash it and return the
        // result as-is. For other types of objects, we will try to normalize them to avoid
        // loading the whole buffer into system memory and encode each "part" using JSON.
        if ($target instanceof JsonSerializable) {
            return hash($algorithm, json_encode($target), true, $options);
        } elseif ($target instanceof Jsonable) {
            return hash($algorithm, $target->toJson(), true, $options);
        } elseif (is_string($target) || $target instanceof Stringable) {
            return hash($algorithm, $target, true, $options);
        } else

        $context = hash_init($algorithm, 0, '', $options);

        foreach ($this->normalizeTarget($target) as $memberOrLine) {
            hash_update($context, json_encode($memberOrLine, JSON_THROW_ON_ERROR));
        }

        return hash_final($context, true);
    }

    /**
     * Normalize the fingerprintable target into an iterable object for hashing.
     *
     * @param  mixed  $target
     * @return iterable
     */
    protected function normalizeTarget($target)
    {
        return match (true) {
            is_resource($target) => new LazyCollection(function () use ($target) {
                rewind($target);

                while (!feof($target)) {
                    yield fgets($target);
                }
            }),
            is_iterable($target) => new LazyCollection(function () use ($target) {
                foreach ($target as $value) {
                    yield $value;
                }
            }),
            is_array($target) => $target,
            $target instanceof Arrayable => $target->toArray(),
            default => [$target],
        };
    }

    /**
     * Determines if this fingerprint hash and the issued hash are the same.
     *
     * @param  mixed  $expected
     * @param  mixed  $string
     * @return bool
     */
    public function is($expected, $string)
    {
        return hash_equals($expected, $string);
    }

    /**
     * Determines if this fingerprint hash and the issued hash are different.
     *
     * @param  mixed  $expected
     * @param  mixed  $string
     * @return bool
     */
    public function isNot($expected, $string)
    {
        return !$this->is($expected, $string);
    }
}
