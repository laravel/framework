<?php

namespace Illuminate\Foundation\ConsoleDumps;

use Illuminate\Foundation\Console\CliDumper;
use Symfony\Component\VarDumper\Cloner\Data;

class DumpRenderer
{
    /**
     * Create a new dump renderer instance.
     */
    public function __construct(
        protected CliDumper $dumper,
        protected string $basePath,
    ) {}

    /**
     * Render an incoming dump.
     *
     * @param  array<string, mixed>  $context
     */
    public function render(Data $data, array $context = []): void
    {
        $this->dumper->dumpWithSource($data, $this->resolveSource($context));
    }

    /**
     * Resolve the source from the dump context.
     *
     * @param  array<string, mixed>  $context
     * @return array{0: string, 1: string, 2: int|null}|null
     */
    protected function resolveSource(array $context): ?array
    {
        $source = $context['source'] ?? null;

        if (! is_array($source)) {
            return null;
        }

        $file = $source['file'] ?? null;

        if (! is_string($file)) {
            return null;
        }

        $relativeFile = is_string($source['file_relative'] ?? null)
            ? $source['file_relative']
            : $file;

        if (! isset($source['file_relative']) && str_starts_with($file, $this->basePath)) {
            $relativeFile = substr($file, strlen($this->basePath) + 1);
        }

        $line = is_int($source['line'] ?? null) ? $source['line'] : null;

        return [$file, $relativeFile, $line];
    }
}
