<?php

namespace Illuminate\Support\EnvProcessors;

final class TrimProcessor implements EnvProcessorInterface
{
    /**
     * Create a new trim processor instance.
     *
     * @param  string|null  $charactersToTrim
     * @return void
     */
    public function __construct(private ?string $charactersToTrim = null)
    {
    }

    /**
     * Sets the characters which are going to be trimmed.
     *
     * @param  string  $charactersToTrim
     * @return self
     */
    public function charactersToTrim(string $charactersToTrim): self
    {
        $processor = clone $this;
        $processor->charactersToTrim = $charactersToTrim;

        return $processor;
    }

    /**
     * Trims the given env variable value.
     *
     * @param  string  $value
     * @return string
     */
    public function __invoke(string $value): string
    {
        if (null !== $this->charactersToTrim) {
            return trim($value, $this->charactersToTrim);
        }

        return trim($value);
    }
}
