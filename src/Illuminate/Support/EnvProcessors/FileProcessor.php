<?php

namespace Illuminate\Support\EnvProcessors;

use LogicException;

final class FileProcessor implements EnvProcessorInterface
{
    /**
     * Returns the content of the file at the given path.
     *
     * @param  string  $value
     * @return string
     *
     * @throws \LogicException
     */
    public function __invoke(string $value): string
    {
        if ('' === $value) {
            throw new LogicException('File path must be a non empty string');
        }

        if (! is_file($value)) {
            throw new LogicException(sprintf('File at path "%s" does not exist', $value));
        }

        return file_get_contents($value);
    }
}
