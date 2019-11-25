<?php

namespace Illuminate\Foundation\Observers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use ReflectionClass;
use ReflectionMethod;
use SplFileInfo;
use Symfony\Component\Finder\Finder;

class DiscoverObservers
{
    /**
     * Get all of the observers by searching the given observer directory.
     *
     * @param  string  $observerPath
     * @param  string  $basePath
     * @return array
     */
    public static function within($observerPath, $basePath)
    {
        return collect(static::getObservers(
            (new Finder())->files()->in($observerPath),
            $basePath
        ))->all();
    }

    /**
     * Get all of the observers and their corresponding models.
     *
     * @param  iterable  $observers
     * @param  string    $basePath
     * @return arrays
     */
    protected static function getObservers($observers, $basePath)
    {
        $observerMapping = [];

        foreach ($observers as $observer) {
            $observer = new ReflectionClass(
                static::classFromFile($observer, $basePath)
            );

            if (! $observer->isInstantiable()) {
                continue;
            }

            foreach ($observer->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
                $parameter = optional($method->getParameters()[0]->getClass());

                if ($parameter && $parameter->isSubclassOf(Model::class)) {
                    $observerMapping[$parameter->name][] = $observer->name;

                    break;
                }
            }
        }

        return $observerMapping;
    }

    /**
     * Extract the class name from the given file path.
     *
     * @param \SplFileInfo $file
     * @param string       $basePath
     * @return string
     */
    protected static function classFromFile(SplFileInfo $file, $basePath)
    {
        $class = trim(Str::replaceFirst($basePath, '', $file->getRealPath()), DIRECTORY_SEPARATOR);

        return str_replace(
            [DIRECTORY_SEPARATOR, ucfirst(basename(app()->path())).'\\'],
            ['\\', app()->getNamespace()],
            ucfirst(Str::replaceLast('.php', '', $class))
        );
    }
}
