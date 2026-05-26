<?php

namespace Illuminate\Validation;

use Exception;
use Illuminate\Contracts\Validation\UncompromisedVerifier;
use Illuminate\Foundation\Application;
use Illuminate\Support\Stringable;

class NotPwnedVerifier implements UncompromisedVerifier
{
    /**
     * The HTTP factory instance.
     *
     * @var \Illuminate\Http\Client\Factory
     */
    protected $factory;

    /**
     * The number of seconds the request can run before timing out.
     *
     * @var int
     */
    protected $timeout;

    /**
     * The User-Agent header sent with requests to the Have I Been Pwned API.
     *
     * @var string|null
     */
    protected $userAgent;

    /**
     * Create a new uncompromised verifier.
     *
     * @param  \Illuminate\Http\Client\Factory  $factory
     * @param  int|null  $timeout
     */
    public function __construct($factory, $timeout = null)
    {
        $this->factory = $factory;
        $this->timeout = $timeout ?? 30;
    }

    /**
     * Set the User-Agent header sent with requests to the Have I Been Pwned API.
     *
     * @param  string  $userAgent
     * @return $this
     */
    public function withUserAgent(string $userAgent)
    {
        $this->userAgent = $userAgent;

        return $this;
    }

    /**
     * Verify that the given data has not been compromised in public breaches.
     *
     * @param  array  $data
     * @return bool
     */
    public function verify($data)
    {
        $value = $data['value'];
        $threshold = $data['threshold'];

        if (empty($value = (string) $value)) {
            return false;
        }

        [$hash, $hashPrefix] = $this->getHash($value);

        return ! $this->search($hashPrefix)
            ->contains(function ($line) use ($hash, $hashPrefix, $threshold) {
                [$hashSuffix, $count] = explode(':', $line);

                return $hashPrefix.$hashSuffix === $hash && $count > $threshold;
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
     * Search by the given hash prefix and returns all occurrences of leaked passwords.
     *
     * @param  string  $hashPrefix
     * @return \Illuminate\Support\Collection
     */
    protected function search($hashPrefix)
    {
        try {
            $response = $this->factory->withHeaders([
                'Add-Padding' => true,
                'User-Agent' => $this->userAgent ?? $this->defaultUserAgent(),
            ])->timeout($this->timeout)->get(
                'https://api.pwnedpasswords.com/range/'.$hashPrefix
            );
        } catch (Exception $e) {
            report($e);
        }

        $body = (isset($response) && $response->successful())
            ? $response->body()
            : '';

        return (new Stringable($body))->trim()->explode("\n")->filter(function ($line) {
            return str_contains($line, ':');
        });
    }

    /**
     * Get the default User-Agent header sent with requests to the Have I Been Pwned API.
     *
     * @return string
     */
    protected function defaultUserAgent()
    {
        // App name intentionally omitted — leaking it to a third-party API requires explicit opt-in via withUserAgent().
        if (class_exists(Application::class)) {
            return 'Laravel/'.Application::VERSION;
        }

        return 'Laravel';
    }
}
