<?php

namespace Illuminate\Foundation;

use Symfony\Component\VarDumper\Caster\Caster;

class ApplicationCaster
{
    /**
     * Application methods to include in the presenter.
     *
     * @var array
     */
    private static $appProperties = [
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
        'bootstrapPath',
    ];

    /**
     * Get an array representing the properties of an application.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return array
     */
    public static function cast($app)
    {
        $results = [];
        foreach (self::$appProperties as $property) {
            try {
                $val = $app->$property();
                if (! is_null($val)) {
                    $results[Caster::PREFIX_VIRTUAL.$property] = $val;
                }
            } catch (Exception $e) {
                //
            }
        }

        return $results;
    }
}
