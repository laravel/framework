<?php

namespace Illuminate\Foundation\Console\Tinker\Casters;

use Exception;
use Illuminate\Support\Collection;
use Illuminate\Foundation\Application;
use Illuminate\Database\Eloquent\Model;
use Symfony\Component\VarDumper\Cloner\Stub;
use Symfony\Component\VarDumper\Caster\Caster;

class FoundationCaster
{
    /**
     * Illuminate application methods to include in the presenter.
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
    ];

    /**
     * Get an array representing the properties of an application.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @param  array  $a
     * @param  Stub  $stub
     * @param  bool  $isNested
     * @param  int  $filter
     * @return array
     */
    public static function castApplication(Application $app, array $a, Stub $stub, $isNested, $filter = 0)
    {
        $a = [];

        foreach (self::$appProperties as $property) {
            try {
                $val = $app->$property();
                if (!is_null($val)) {
                    $a[Caster::PREFIX_VIRTUAL . $property] = $val;
                }
            } catch (Exception $e) {
                //
            }
        }

        return $a;
    }

    /**
     * Get an array representing the properties of a collection.
     *
     * @param  \Illuminate\Support\Collection  $value
     * @param  array  $a
     * @param  Stub  $stub
     * @param  bool  $isNested
     * @param  int  $filter
     * @return array
     */
    public static function castCollection(Collection $coll, array $a, Stub $stub, $isNested, $filter = 0)
    {
        return [
            Caster::PREFIX_VIRTUAL.'all' => $coll->all(),
        ];
    }

    /**
     * Get an array representing the properties of a model.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @param  array  $a
     * @param  Stub  $stub
     * @param  bool  $isNested
     * @param  int  $filter
     * @return array
     */
    public static function castModel(Model $model, array $a, Stub $stub, $isNested, $filter = 0)
    {
        $attributes = array_merge($model->getAttributes(), $model->getRelations());
        $visible = array_flip($model->getVisible() ?: array_diff(array_keys($attributes), $model->getHidden()));
        $attributes = array_intersect_key($attributes, $visible);

        $a = [];
        foreach ($attributes as $key => $value) {
            $a[(isset($visible[$key]) ? Caster::PREFIX_VIRTUAL : Caster::PREFIX_PROTECTED).$key] = $value;
        }

        return $a;
    }
}
