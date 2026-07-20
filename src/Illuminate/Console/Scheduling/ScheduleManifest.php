<?php

namespace Illuminate\Console\Scheduling;

use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Filesystem\Filesystem;
use RuntimeException;

class ScheduleManifest
{
    /**
     * Create a new schedule manifest.
     *
     * @param  Filesystem  $files
     * @param  string  $path
     */
    public function __construct(
        protected Filesystem $files,
        protected string $path,
    ) {
        //
    }

    /**
     * Determine whether the manifest exists.
     *
     * @return bool
     */
    public function exists(): bool
    {
        return $this->files->exists($this->path);
    }

    /**
     * Load the discovered tasks from the manifest.
     *
     * @return array<int, DiscoveredScheduledTask>
     *
     * @throws FileNotFoundException
     */
    public function load(): array
    {
        if (! $this->exists()) {
            return [];
        }

        $manifest = $this->files->getRequire($this->path);

        if (! is_array($manifest)) {
            throw new RuntimeException(
                'The cached schedule manifest must return an array.'
            );
        }

        return array_map(
            static fn (array $task) => DiscoveredScheduledTask::fromArray($task),
            $manifest,
        );
    }

    /**
     * Write the discovered tasks to the manifest.
     *
     * @param  array<int, DiscoveredScheduledTask>  $tasks
     * @return void
     */
    public function write(array $tasks): void
    {
        $this->files->ensureDirectoryExists(
            dirname($this->path)
        );

        $contents = '<?php'.PHP_EOL.PHP_EOL
            .'return '
            .var_export(
                array_map(
                    static fn (DiscoveredScheduledTask $task) => $task->toArray(),
                    $tasks,
                ),
                true,
            )
            .';'.PHP_EOL;

        $this->files->replace(
            $this->path,
            $contents,
        );
    }

    /**
     * Delete the manifest.
     *
     * @return bool
     */
    public function clear(): bool
    {
        return $this->files->delete($this->path);
    }

    /**
     * Get the manifest path.
     *
     * @return string
     */
    public function path(): string
    {
        return $this->path;
    }
}
