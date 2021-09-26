<?php

namespace Illuminate\Support\EnvProcessors;

final class BooleanProcessor implements EnvProcessorInterface
{
    /**
     * Create a new boolean processor instance.
     *
     * @param  bool  $not
     * @return void
     */
    public function __construct(private bool $not = false)
    {
    }

    /**
     * Negates the boolean value.
     *
     * @return self
     */
    public function not(): self
    {
        $processor = clone $this;
        $processor->not = true;

        return $processor;
    }

    /**
     * Converts the env value to its boolean representation.
     *
     * @param  string  $value
     * @return bool
     */
    public function __invoke(string $value): bool
    {
        $returnValue = (bool) (
            filter_var($value, \FILTER_VALIDATE_BOOLEAN)
                ?: filter_var($value, \FILTER_VALIDATE_INT)
                ?: filter_var($value, \FILTER_VALIDATE_FLOAT)
        );

        return $this->not ? ! $returnValue : $returnValue;
    }
}
