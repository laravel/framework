<?php

namespace Illuminate\Support\EnvProcessors;

use LogicException;

final class ChainProcessor implements EnvProcessorInterface
{
    /**
     * @var EnvProcessorInterface[]
     */
    private array $processors;

    /**
     * Create a new chain processor instance.
     *
     * @param  \Illuminate\Support\EnvProcessors\EnvProcessorInterface  ...$processors
     * @return void
     */
    public function __construct(EnvProcessorInterface ...$processors)
    {
        $countOfProcessors = count($processors);

        if ($countOfProcessors < 2) {
            throw new LogicException(sprintf('The chain processor expects at least two processors, %d given', $countOfProcessors));
        }

        $this->processors = $processors;
    }

    /**
     * Converts the env value to its final representation by using the given processors.
     *
     * @param  string  $value
     * @return mixed
     */
    public function __invoke(string $value): mixed
    {
        foreach ($this->processors as $processor) {
            $value = $processor($value);
        }

        return $value;
    }
}
