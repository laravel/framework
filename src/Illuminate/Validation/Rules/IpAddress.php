<?php

namespace Illuminate\Validation\Rules;

use InvalidArgumentException;
use Stringable;

class IpAddress implements Stringable
{
    protected ?int $version = null;
    private array $allowedVersions = [4, 6];

    /**
     * Set the IP version.
     *
     * @throws InvalidArgumentException
     */
    public function version(int $version): static
    {
        if (! in_array($version, $this->allowedVersions)) {
            throw new InvalidArgumentException(sprintf(
                'The provided IP version %d is invalid. Please use one of these: %s',
                $version,
                implode(', ', $this->allowedVersions),
            ));
        }

        $this->version = $version;

        return $this;
    }

    public function __toString(): string
    {
        return $this->version === null ? 'ip' : "ipv{$this->version}";
    }
}
