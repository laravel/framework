<?php

namespace Illuminate\Foundation\Console\Tinker\Casters;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Application;
use Illuminate\Support\Collection;
use Symfony\Component\VarDumper\Caster\Caster;
use Symfony\Component\VarDumper\Cloner\Stub;

class FoundationCaster
{
    /**
     * Illuminate Application methods to include in the presenter.
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
     * Get an array representing the properties of an Application object.
     *
     * @param  Application  $value
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
                $val = $value->$property();
                if ( ! is_null($val)) {
                    $a[Caster::PREFIX_VIRTUAL . $property] = $val;
                }
            } catch (Exception $e) {
            }
        }

        return $a;
    }

    /**
     * Get an array representing the properties of a Collection.
     *
     * @param  Collection  $value
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
     * Get an array representing the properties of a Model object.
     *
     * @param  Application  $value
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
