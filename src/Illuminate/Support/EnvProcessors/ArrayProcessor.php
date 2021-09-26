<?php

namespace Illuminate\Support\EnvProcessors;

final class ArrayProcessor implements EnvProcessorInterface
{
    private const NO_ADDITIONAL_PROCESSING_MODE = 0;
    private const INT_MODE = 1;
    private const FLOAT_MODE = 2;

    private int $mode = self::NO_ADDITIONAL_PROCESSING_MODE;

    /**
     * Create a new array processor instance.
     *
     * @param  string  $delimiter
     * @return void
     */
    public function __construct(private string $delimiter = ',')
    {
    }

    /**
     * Sets a delimiter by which to explode the string by.
     *
     * @param  string  $delimiter
     * @return self
     */
    public function delimiter(string $delimiter): self
    {
        $processor = clone $this;
        $processor->delimiter = $delimiter;

        return $processor;
    }

    /**
     * Casts all array elements to integers.
     *
     * @return self
     */
    public function integerElements(): self
    {
        $processor = clone $this;
        $processor->mode = self::INT_MODE;

        return $processor;
    }

    /**
     * Casts all array elements to floats.
     *
     * @return self
     */
    public function floatElements(): self
    {
        $processor = clone $this;
        $processor->mode = self::FLOAT_MODE;

        return $processor;
    }

    /**
     * Converts the env value to its array representation.
     *
     * @param  string  $value
     * @return array
     */
    public function __invoke(string $value): array
    {
        if ('null' === $value) {
            return [];
        }

        $value = explode($this->delimiter, $value);

        if ($this->mustHaveIntegerElements()) {
            return array_map('intval', $value);
        }

        if ($this->mustHaveFloatElements()) {
            return array_map('floatval', $value);
        }

        return $value;
    }

    /**
     * Checks whether the elements of the array have to be cast into integers.
     *
     * @return bool
     */
    private function mustHaveIntegerElements(): bool
    {
        return self::INT_MODE === $this->mode;
    }

    /**
     * Checks whether the elements of the array have to be cast into floats.
     *
     * @return bool
     */
    private function mustHaveFloatElements(): bool
    {
        return self::FLOAT_MODE === $this->mode;
    }
}
