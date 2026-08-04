<?php

namespace Illuminate\Foundation\ConsoleDumps;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Foundation\Concerns\ResolvesDumpSource;
use Symfony\Component\VarDumper\Caster\ScalarStub;
use Throwable;

class DumpHelper
{
    use ResolvesDumpSource;

    /**
     * The application base path.
     *
     * @var string
     */
    protected $basePath;

    /**
     * The compiled view path for the application.
     *
     * @var string|null
     */
    protected $compiledViewPath;

    /**
     * Create a new dump helper instance.
     */
    public function __construct(
        protected DumpClient $client,
        Application $app,
    ) {
        $this->basePath = $app->basePath();
        $this->compiledViewPath = $app['config']->get('view.compiled');
    }

    /**
     * Send values to the dump server.
     */
    public function dump(mixed ...$values): mixed
    {
        try {
            $context = $this->context();

            if ($values === []) {
                $this->client->dump(new ScalarStub('🐛'), $context);
            } elseif (array_key_exists(0, $values) && count($values) === 1) {
                $this->client->dump($values[0], $context);
            } else {
                foreach ($values as $key => $value) {
                    $this->client->dump($value, $context, is_int($key) ? (string) ($key + 1) : $key);
                }
            }
        } catch (Throwable) {
            // Dumps should never affect the application.
        }

        if ($values === []) {
            return null;
        }

        return count($values) === 1 ? $values[array_key_first($values)] : $values;
    }

    /**
     * Resolve the current dump context.
     *
     * @return array{source?: array{file: string, file_relative: string, line: int|null}}
     */
    protected function context(): array
    {
        if (is_null($source = $this->source())) {
            return [];
        }

        [$file, $relativeFile, $line] = $source;

        return [
            'source' => [
                'file' => $file,
                'file_relative' => $relativeFile,
                'line' => $line,
            ],
        ];
    }

    /**
     * Resolve the source of the current dump call.
     *
     * @return array{0: string, 1: string, 2: int|null}|null
     */
    protected function source(): ?array
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5);
        $source = null;

        foreach ($trace as $frame) {
            if (($frame['function'] ?? null) === 'dc' && ! isset($frame['class'])) {
                $source = $frame;
            }

            if (($frame['function'] ?? null) === 'dc' && isset($frame['class'])) {
                $source = $frame;

                break;
            }

            if (($frame['function'] ?? null) === 'dump' && is_a($frame['class'] ?? '', self::class, true)) {
                $source = $frame;
            }
        }

        $file = $source['file'] ?? null;
        $line = $source['line'] ?? null;

        if (! is_string($file) || ! is_int($line)) {
            return null;
        }

        if (is_string($this->compiledViewPath) && $this->compiledViewPath !== '' && $this->isCompiledViewFile($file)) {
            $file = $this->getOriginalFileForCompiledView($file);
            $line = null;
        }

        $relativeFile = str_starts_with($file, $this->basePath)
            ? substr($file, strlen($this->basePath) + 1)
            : $file;

        return [$file, $relativeFile, $line];
    }
}
