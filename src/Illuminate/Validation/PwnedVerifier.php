<?php

namespace Illuminate\Validation;

use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\ClientInterface as HttpClientInterface;

class PwnedVerifier
{
    /**
     * The HaveIBeenPwned API URL.
     */
    public const PWNED_API = 'https://api.pwnedpasswords.com/range/%s';

    /**
     * @var HttpClientInterface
     */
    private $httpClient;

    public function __construct(HttpClientInterface $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    /**
     * Checks if password has been leaked from a data breach using the HaveIBeenPwned API.
     *
     * @param  string  $password
     * @param  int     $threshold
     * @param  bool    $skipOnError
     * @return bool
     * @throws GuzzleException
     */
    public function isPwned(string $password, int $threshold = 1, bool $skipOnError = false): bool
    {
        $hash = strtoupper(sha1($password));
        $hashPrefix = substr($hash, 0, 5);
        $hashSuffix = substr($hash, 5);

        $url = sprintf(self::PWNED_API, $hashPrefix);

        try {
            $result = $this->httpClient->request('GET', $url)->getBody()->getContents();

            foreach (explode("\r\n", $result) as $line) {
                [$suffix, $count] = explode(':', $line);

                if ($hashSuffix === $suffix && (int) $count >= $threshold) {
                    return true;
                }
            }

            return false;
        } catch (GuzzleException $e) {
            if ($skipOnError) {
                return false;
            }

            throw $e;
        }
    }

    /**
     * @param  string  $password
     * @param  int     $threshold
     * @param  bool    $skipOnError
     * @return bool
     * @throws GuzzleException
     */
    public function isNotPwned(string $password, int $threshold = 1, bool $skipOnError = false): bool
    {
        return ! $this->isPwned($password, $threshold, $skipOnError);
    }
}
