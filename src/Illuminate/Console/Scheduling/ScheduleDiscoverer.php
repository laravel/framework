<?php

namespace Illuminate\Console\Scheduling;

use Illuminate\Console\Scheduling\Attributes\Scheduled;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

class ScheduleDiscoverer
{
    /**
     * Discover scheduled tasks within the given application path.
     *
     * @param  string  $path
     * @param  string  $namespace
     * @return array<int, DiscoveredScheduledTask>
     */
    public function discover(string $path, string $namespace): array
    {
        if (! is_dir($path)) {
            return [];
        }

        $tasks = [];

        $files = Finder::create()
            ->files()
            ->name('*.php')
            ->in($path);

        foreach ($files as $file) {
            $class = $this->classFromFile(
                file: $file,
                path: $path,
                namespace: $namespace,
            );

            if ($class === null || ! class_exists($class)) {
                continue;
            }

            try {
                $reflection = new ReflectionClass($class);
            } catch (ReflectionException) {
                continue;
            }

            if (! $reflection->isInstantiable()) {
                continue;
            }

            $this->discoverClassAttributes($reflection, $tasks);
            $this->discoverMethodAttributes($reflection, $tasks);
        }

        return $tasks;
    }

    /**
     * Discover attributes on an invokable class.
     *
     * @param  ReflectionClass<object>  $reflection
     * @param  array<int, DiscoveredScheduledTask>  $tasks
     * @return void
     */
    protected function discoverClassAttributes(
        ReflectionClass $reflection,
        array &$tasks
    ): void {
        $attributes = $reflection->getAttributes(
            Scheduled::class,
            ReflectionAttribute::IS_INSTANCEOF,
        );

        if ($attributes === [] || ! $reflection->hasMethod('__invoke')) {
            return;
        }

        $method = $reflection->getMethod('__invoke');

        if (! $method->isPublic() || $method->isStatic()) {
            return;
        }

        foreach ($attributes as $attribute) {
            $tasks[] = new DiscoveredScheduledTask(
                class: $reflection->getName(),
                method: '__invoke',
                schedule: $attribute->newInstance(),
            );
        }
    }

    /**
     * Discover attributes on public instance methods.
     *
     * @param  ReflectionClass<object>  $reflection
     * @param  array<int, DiscoveredScheduledTask>  $tasks
     * @return void
     */
    protected function discoverMethodAttributes(
        ReflectionClass $reflection,
        array &$tasks
    ): void {
        foreach (
            $reflection->getMethods(ReflectionMethod::IS_PUBLIC)
            as $method
        ) {
            if (
                $method->isStatic()
                || $method->isConstructor()
                || $method->isDestructor()
                || $method->getDeclaringClass()->getName()
                !== $reflection->getName()
            ) {
                continue;
            }

            $attributes = $method->getAttributes(
                Scheduled::class,
                ReflectionAttribute::IS_INSTANCEOF,
            );

            foreach ($attributes as $attribute) {
                $tasks[] = new DiscoveredScheduledTask(
                    class: $reflection->getName(),
                    method: $method->getName(),
                    schedule: $attribute->newInstance(),
                );
            }
        }
    }

    /**
     * Resolve the class represented by a PSR-4 application file.
     *
     * @param  SplFileInfo  $file
     * @param  string  $path
     * @param  string  $namespace
     * @return class-string|null
     */
    protected function classFromFile(
        SplFileInfo $file,
        string $path,
        string $namespace
    ): ?string {
        $basePath = realpath($path);
        $filePath = $file->getRealPath();

        if ($basePath === false || $filePath === false) {
            return null;
        }

        $relativePath = substr(
            $filePath,
            strlen($basePath) + 1,
        );

        if ($relativePath === false) {
            return null;
        }

        $class = str_replace(
            [DIRECTORY_SEPARATOR, '.php'],
            ['\\', ''],
            $relativePath,
        );

        return rtrim($namespace, '\\').'\\'.$class;
    }
}
