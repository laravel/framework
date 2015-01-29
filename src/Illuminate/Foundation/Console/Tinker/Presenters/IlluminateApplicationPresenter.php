<?php namespace Illuminate\Foundation\Console\Tinker\Presenters;

use Psy\Presenter\ObjectPresenter;
use Illuminate\Foundation\Application;

class IlluminateApplicationPresenter extends ObjectPresenter {

    protected static $appProps = [
        'configurationIsCached',
        'environment',
        'environmentFile',
        'isLocal',
        'routesAreCached',
        'runningUnitTests',
        'version',
        'path',
        'basePath',
        'configPath',
        'databasePath',
        'langPath',
        'publicPath',
        'storagePath',
    ];

    /**
     * IlluminateApplicationPresenter can present Models.
     *
     * @param mixed $value
     * @return boolean
     */
    public function canPresent($value)
    {
        return $value instanceof Application;
    }

    /**
     * Get an array of Application object properties.
     *
     * @param object           $value
     * @param \ReflectionClass $class
     * @param int              $propertyFilter One of \ReflectionProperty constants
     * @return array
     */
    public function getProperties($value, \ReflectionClass $class, $propertyFilter)
    {
        $props = [];

        foreach (self::$appProps as $prop) {
            try {
                $val = $value->$prop();
                if ($val !== null) {
                    $props[$prop] = $val;
                }
            } catch (\Exception $e) {
                // Ignore exceptions
            }
        }

        return $props;
    }
}
