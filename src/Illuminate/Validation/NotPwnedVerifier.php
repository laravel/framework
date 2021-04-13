<?php

namespace Illuminate\Validation;

use Exception;
use Illuminate\Contracts\Validation\NotCompromisedVerifier;
use Illuminate\Http\Client\Factory;
use Illuminate\Support\Str;

class NotPwnedVerifier implements NotCompromisedVerifier
{
    /**
     * The http factory instance.
     *
     * @var \Illuminate\Http\Client\Factory
     */
    protected $factory;

    /**
     * Create a new not compromised verifier.
     *
     * @param  \Illuminate\Http\Client\Factory  $factory
     * @return void
     */
    public function __construct($factory)
    {
        $this->factory = $factory;
    }

    /**
     * Verify that the given value has not been compromised in public breaches.
     *
     * @param  string  $value
     * @return bool
     */
    public function verify($value)
    {
        if (empty($value = (string) $value)) {
            return false;
        }

        [$hash, $hashPrefix] = $this->getHash($value);

        return ! $this->search($hashPrefix)
            ->contains(function ($line) use ($hash, $hashPrefix) {
                [$hashSuffix, $count] = explode(':', $line);

                return $hashPrefix.$hashSuffix == $hash && $count > 0;
            });
    }

    /**
     * Get the hash and its first 5 chars.
     *
     * @param  string  $value
     * @return array
     */
    protected function getHash($value)
    {
        $hash = strtoupper(sha1((string) $value));
        $hashPrefix = substr($hash, 0, 5);

        return [$hash, $hashPrefix];
    }

    /**
     * Search by the given hash prefix and returns all occurrences.
     *
     * @param  string $hashPrefix
     * @return \Illuminate\Support\Collection
     */
    protected function search($hashPrefix)
    {
        try {
            $response = $this->factory->withHeaders([
                'Add-Padding' => true,
            ])->get(
                'https://api.pwnedpasswords.com/range/'.$hashPrefix
            );
        } catch (Exception $e) {
            report($e);
        }

        $body = (isset($response) && $response->successful())
            ? $response->body()
            : '';

        return Str::of($body)->trim()->explode("\n")->filter(function ($line) {
            return Str::contains($line, ':');
        });
    }
}
