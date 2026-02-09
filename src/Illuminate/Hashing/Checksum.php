<?php

namespace Illuminate\Hashing;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Support\Traits\Conditionable;
use Illuminate\Support\Traits\Macroable;
use Illuminate\Support\Traits\Tappable;
use InvalidArgumentException;
use JsonSerializable;
use RuntimeException;
use SplFileInfo as File;
use Stringable;
use function hash_final;

class Checksum implements Stringable
{
    use Macroable, Conditionable, Tappable;

    /**
     * The algorithm that was used to calculate the checksum.
     *
     * @var string
     */
    protected $algorithm;

    /**
     * The options to pass to the algorithm.
     *
     * @var array|int|null
     */
    protected $options;

    /**
     * The calculated checksum.
     *
     * @var string
     */
    protected $checksum;

    /**
     * Adds a "seed" to the checksum calculation.
     *
     * @param  string|int  $seed
     * @return $this
     */
    public function withSeed($seed)
    {
        $this->options['seed'] = $seed;

        return $this;
    }

    /**
     * Adds a "secret" to the checksum calculation.
     *
     * @param  string  $secret
     * @return $this
     */
    public function withSecret($secret)
    {
        $this->options['secret'] = $secret;

        return $this;
    }

    /**
     * Calculate a checksum using CRC32.
     *
     * @param  \Illuminate\Contracts\Support\Jsonable|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable|\SplFileInfo|resource|string  $data
     * @return $this
     */
    public function crc32($data)
    {
        $this->algorithm = 'crc32b';
        $this->checksum = $this->calculate($data);

        return $this;
    }

    /**
     * Calculate a checksum using MD5.
     *
     * @param  \Illuminate\Contracts\Support\Jsonable|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable|\SplFileInfo|resource|string  $data
     * @return $this
     */
    public function md5($data)
    {
        $this->algorithm = 'md5';
        $this->checksum = $this->calculate($data);

        return $this;
    }

    /**
     * Calculate a checksum using MD5.
     *
     * @param  \Illuminate\Contracts\Support\Jsonable|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable|\SplFileInfo|resource|string  $data
     * @return $this
     */
    public function sha256($data)
    {
        $this->algorithm = 'sha256';
        $this->checksum = $this->calculate($data);

        return $this;
    }

    /**
     * Calculate a checksum using XXH3.
     *
     * @param  \Illuminate\Contracts\Support\Jsonable|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable|\SplFileInfo|resource|string  $data
     * @return $this
     */
    public function xxh3($data)
    {
        $this->algorithm = 'xxh3';
        $this->checksum = $this->calculate($data);

        return $this;
    }

    /**
     * Calculate a checksum using XXH3.
     *
     * @param  \Illuminate\Contracts\Support\Jsonable|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable|\SplFileInfo|resource|string  $data
     * @return $this
     */
    public function xxh128($data)
    {
        $this->algorithm = 'xxh128';
        $this->checksum = $this->calculate($data);

        return $this;
    }

    /**
     * Calculate the checksum.
     *
     * @param  \Illuminate\Contracts\Support\Jsonable|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable|\SplFileInfo|resource|string  $data
     * @return string
     */
    protected function calculate($data)
    {
        if (is_resource($data)) {
            return $this->calculateResourceHash($data);
        }

        if ($data instanceof File) {
            return $this->calculateFileHash($data);
        }

        $data = match (true) {
            is_array($data),
            $data instanceof JsonSerializable => json_encode($data),
            $data instanceof Jsonable => $data->toJson(),
            $data instanceof Arrayable => json_encode($data->toArray()),
            default => (string) $data,
        };

        return hash($this->algorithm, $data, $this->options);
    }

    /**
     * Calculate the hash of a resource using native functions.
     *
     * @param  resource  $data
     * @return string
     */
    protected function calculateResourceHash($data)
    {
        $context = hash_init($this->algorithm, $this->options);

        hash_update_stream($context, $data);

        $final = hash_final($context);

        rewind($data);
        
        return $final;
    }

    /**
     * Calculate the hash of a file using native functions.
     *
     * @param  \SplFileInfo  $data
     * @return string
     */
    protected function calculateFileHash($data)
    {
        $path = $data->getRealPath();

        if (!$path) {
            throw new RuntimeException('Unable to open the file real path.');
        }

        return hash_file($this->algorithm, $path, $this->options);
    }

    /**
     * Determine if the calculated hash is equal to another hash.
     *
     * @param  string  $hash
     * @return bool
     */
    public function is($hash)
    {
        return hash_equals($this->checksum, $hash);
    }

    /**
     * Determine if the calculated hash is not equal to another hash.
     *
     * @param  string  $hash
     * @return bool
     */
    public function isNot($hash)
    {
        return !$this->is($hash);
    }

    /**
     * Determine if the calculated hash is equal to the given data.
     *
     * @param  \Illuminate\Contracts\Support\Jsonable|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable|\SplFileInfo|resource|string  $comparable
     * @return bool
     */
    public function isSameHashOf($comparable)
    {
        return $this->is($this->calculate($comparable));
    }

    /**
     * Determine if the calculated hash is not equal to the given data.
     *
     * @param  \Illuminate\Contracts\Support\Jsonable|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable|\SplFileInfo|resource|string  $comparable
     * @return bool
     */
    public function isNotSameHashOf($comparable)
    {
        return !$this->isSameHashOf($comparable);
    }

    /**
     * Returns the checksum hash.
     *
     * @return string
     */
    public function hash()
    {
        return $this->checksum ?? throw new RuntimeException('No checksum was calculated.');
    }

    /**
     * Returns the checksum as a string.
     *
     * @return string
     */
    public function toString()
    {
        return $this->hash();
    }

    /**
     * Return the checksum as a string representation.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->hash();
    }

    /**
     * Returns the checksum as a binary string.
     *
     * @return string
     */
    public function toBinary()
    {
        return hex2bin($this->hash());
    }
}
